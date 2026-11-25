<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArchitectureRules;

#[CoversNothing]
final class ModuleArchitectureTest extends TestCase
{
    public function testEveryApplicationSourceRespectsTheApprovedBoundaries(): void
    {
        $root = dirname(__DIR__, 2);
        $rules = new ArchitectureRules();
        $violations = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/app', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                foreach ($rules->violations(file_get_contents($file->getPathname()), $path) as $violation) {
                    $violations[] = $path.': '.$violation;
                }
            }
        }
        self::assertSame([], $violations);
    }

    #[DataProvider('forbiddenDependencies')]
    public function testDetectsForbiddenDependenciesWithoutExecutingSource(string $path, string $source): void
    {
        self::assertNotEmpty((new ArchitectureRules())->violations('<?php '.$source, $path));
    }

    public static function forbiddenDependencies(): iterable
    {
        $domain = 'app/Modules/Identity/Domain/Account.php';
        $application = 'app/Modules/Identity/Application/Account.php';
        $http = 'app/Modules/Identity/Http/Account.php';
        yield 'domain alias' => [$domain, 'namespace App\Modules\Identity\Domain; use Illuminate\Database\Eloquent\Model as Record; class Account extends Record {}'];
        yield 'domain fully qualified' => [$domain, 'namespace App\Modules\Identity\Domain; class Account { public function run(): \Illuminate\Http\Request {} }'];
        yield 'grouped imports' => [$domain, 'namespace App\Modules\Identity\Domain; use Illuminate\Http\{Request, Response}; class Account { public function run(Request $input): Response {} }'];
        yield 'domain own infrastructure' => [$domain, 'namespace App\Modules\Identity\Domain; class Account { public function run(\App\Modules\Identity\Infrastructure\Persistence\User $user): void {} }'];
        yield 'domain other module' => [$domain, 'namespace App\Modules\Identity\Domain; class Account { public function run(\App\Modules\Recipes\Domain\Recipe $recipe): void {} }'];
        yield 'domain shared application' => [$domain, 'namespace App\Modules\Identity\Domain; use App\Shared\Application\Operation; class Account {}'];
        yield 'unknown module' => ['app/Modules/Unknown/Domain/Account.php', 'namespace App\Modules\Unknown\Domain; class Account {}'];
        yield 'unknown layer' => ['app/Modules/Identity/Other/Account.php', 'namespace App\Modules\Identity\Other; class Account {}'];
        yield 'application infrastructure' => [$application, 'namespace App\Modules\Identity\Application; class Account { public function run(): \App\Modules\Identity\Infrastructure\Persistence\User {} }'];
        yield 'application http' => [$application, 'namespace App\Modules\Identity\Application; use App\Modules\Identity\Http\AccountController; class Account {}'];
        yield 'application shared infrastructure' => [$application, 'namespace App\Modules\Identity\Application; use App\Shared\Infrastructure\RuntimeConfiguration; class Account {}'];
        yield 'cross module internals' => [$application, 'namespace App\Modules\Identity\Application; use App\Modules\Recipes\Infrastructure\Recipe; class Account {}'];
        yield 'request in public contract' => [$application, 'namespace App\Modules\Identity\Application; class Account { public function run(\Illuminate\Http\Request $input): void {} }'];
        yield 'response union' => [$application, 'namespace App\Modules\Identity\Application; class Account { public function run(): \Symfony\Component\HttpFoundation\Response|false {} }'];
        yield 'eloquent public contract' => [$application, 'namespace App\Modules\Identity\Application; class Account { public function run(\Illuminate\Database\Eloquent\Model $input): void {} }'];
        yield 'interface public contract' => [$application, 'namespace App\Modules\Identity\Application; interface Account { public function run(\Illuminate\Http\Request $input): void; }'];
        yield 'http infrastructure' => [$http, 'namespace App\Modules\Identity\Http; use App\Modules\Identity\Infrastructure\Persistence\User; class Account {}'];
        yield 'http domain rule' => [$http, 'namespace App\Modules\Identity\Http; use App\Modules\Identity\Domain\Account; class AccountController {}'];
        yield 'hidden body dependency' => [$domain, 'namespace App\Modules\Identity\Domain; class Account { public function run(): void { new \Illuminate\Http\Request(); } }'];
        yield 'trait dependency' => [$domain, 'namespace App\Modules\Identity\Domain; class Account { use \Illuminate\Database\Eloquent\SoftDeletes; }'];
        yield 'attribute dependency' => [$domain, 'namespace App\Modules\Identity\Domain; #[\Illuminate\Container\Attributes\Config("app.name")] class Account {}'];
        yield 'wrong namespace' => [$domain, 'namespace App\Modules\Recipes\Domain; class Account {}'];
        yield 'missing namespace' => [$domain, 'class Account {}'];
        yield 'syntax failure' => [$domain, 'namespace App\Modules\Identity\Domain; class Account {'];
        yield 'environment lookup' => ['app/Shared/Infrastructure/Adapter.php', 'namespace App\Shared\Infrastructure; class Adapter { public function run() { return env("VALUE"); } }'];
        yield 'environment alias' => ['app/Shared/Infrastructure/Adapter.php', 'namespace App\Shared\Infrastructure; use function getenv as readSetting; class Adapter { public function run() { return readSetting("VALUE"); } }'];
    }

    #[DataProvider('allowedDependencies')]
    public function testAllowsExistingLayerContractsAndNativeFunctions(string $path, string $source): void
    {
        self::assertSame([], (new ArchitectureRules())->violations('<?php '.$source, $path));
    }

    public static function allowedDependencies(): iterable
    {
        yield 'pure domain' => ['app/Modules/Identity/Domain/Account.php', 'namespace App\Modules\Identity\Domain; final class Account { public function run(\DateTimeImmutable $time): string { return trim(" text "); } }'];
        yield 'application domain' => ['app/Modules/Identity/Application/Account.php', 'namespace App\Modules\Identity\Application; use App\Modules\Identity\Domain\Account as Value; class Account { public function run(Value $value): Value { return $value; } }'];
        yield 'application contracts' => ['app/Modules/Identity/Application/Account.php', 'namespace App\Modules\Identity\Application; use App\Modules\Recipes\Application\RecipeLookup; class Account { public function run(RecipeLookup $lookup): void {} }'];
        yield 'infrastructure framework' => ['app/Modules/Identity/Infrastructure/Account.php', 'namespace App\Modules\Identity\Infrastructure; class Account extends \Illuminate\Database\Eloquent\Model {}'];
        yield 'http application' => ['app/Modules/Identity/Http/Account.php', 'namespace App\Modules\Identity\Http; class Account { public function run(\App\Modules\Identity\Application\Account $useCase): void {} }'];
        yield 'anonymous migration' => ['app/Modules/Identity/Infrastructure/Database/Migrations/create.php', 'return new class extends \Illuminate\Database\Migrations\Migration { public function up(): void {} };'];
        yield 'shared domain' => ['app/Modules/Identity/Domain/Account.php', 'namespace App\Modules\Identity\Domain; use App\Shared\Domain\Identifier; class Account { public function run(Identifier $id): void {} }'];
        yield 'central pointer loader' => ['app/Shared/Infrastructure/RuntimeApplicationFactory.php', 'namespace App\Shared\Infrastructure; class RuntimeApplicationFactory { public function run() { return getenv("NATURALLY_CONFIG_PATH"); } }'];
        yield 'comments and strings' => ['app/Modules/Identity/Domain/Account.php', 'namespace App\Modules\Identity\Domain; // Illuminate\Http\Request'."\n".' class Account { public function run(): string { return "Illuminate\\Http\\Request"; } }'];
    }
}
