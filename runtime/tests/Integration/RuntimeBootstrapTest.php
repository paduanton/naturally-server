<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Shared\Infrastructure\LoadRuntimeEnvironment;
use App\Shared\Infrastructure\RuntimeApplicationFactory;
use App\Shared\Infrastructure\RuntimeApplication;
use App\Shared\Infrastructure\RejectUnsafeRuntimeCommand;
use App\Shared\Http\ProblemDetailsRenderer;
use App\Shared\Infrastructure\RuntimeConfiguration;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversFile;
use PHPUnit\Framework\Attributes\UsesFile;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

#[CoversClass(RuntimeApplicationFactory::class)]
#[CoversClass(RuntimeApplication::class)]
#[CoversClass(RejectUnsafeRuntimeCommand::class)]
#[CoversClass(ProblemDetailsRenderer::class)]
#[CoversFile(__DIR__.'/../../config/view.php')]
#[CoversFile(__DIR__.'/../../routes/health.php')]
#[CoversFile(__DIR__.'/../../bootstrap/app.php')]
#[CoversFile(__DIR__.'/../../bootstrap/providers.php')]
#[CoversFile(__DIR__.'/../../public/index.php')]
#[CoversFile(__DIR__.'/../../artisan')]
#[UsesFile(__DIR__.'/../../config/app.php')]
#[UsesFile(__DIR__.'/../../config/database.php')]
#[UsesFile(__DIR__.'/../../config/cache.php')]
#[UsesFile(__DIR__.'/../../config/session.php')]
#[UsesFile(__DIR__.'/../../config/mail.php')]
#[UsesFile(__DIR__.'/../../config/logging.php')]
#[UsesClass(LoadRuntimeEnvironment::class)]
#[UsesClass(RuntimeConfiguration::class)]
final class RuntimeBootstrapTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/naturally-startup-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        mkdir($this->directory.'/secrets', 0700);
        file_put_contents($this->directory.'/secrets/app_key', 'base64:'.base64_encode(random_bytes(32)));
        file_put_contents($this->directory.'/secrets/db_password', bin2hex(random_bytes(24)));
        chmod($this->directory.'/secrets/app_key', 0600);
        chmod($this->directory.'/secrets/db_password', 0600);
        file_put_contents($this->directory.'/settings.json', json_encode([
            'environment' => 'testing', 'debug' => false, 'url' => 'https://naturally.test',
            'database' => ['host' => 'db', 'port' => 3306, 'name' => 'naturally_test', 'user' => 'naturally'],
            'redis' => ['host' => 'redis', 'port' => 6379],
            'mail' => ['host' => 'mailpit', 'port' => 1025],
        ], JSON_THROW_ON_ERROR));
        parent::setUp();
    }

    public function createApplication()
    {
        $application = RuntimeApplicationFactory::create(dirname(__DIR__, 2),
            $this->directory.'/settings.json', $this->directory.'/secrets', $this->directory.'/writable');
        $application->make(Kernel::class)->bootstrap();
        return $application;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeFixture($this->directory);
    }

    public function test_liveness_boots_without_database_redis_or_sessions(): void
    {
        $response = $this->getJson('/health/live')->assertOk()->assertExactJson(['status' => 'up']);
        foreach ($this->app->make('db')->getConnections() as $connection) {
            self::assertFalse($connection->getRawPdo() instanceof \PDO);
        }
        self::assertFalse($this->app->resolved('redis'));
        self::assertCount(0, $response->headers->getCookies());
    }

    public function test_unknown_routes_return_generic_problem_details(): void
    {
        $this->get('/not-found', ['Accept' => 'text/html'])->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertExactJson(['type' => 'about:blank', 'title' => 'Not Found', 'status' => 404]);
    }

    public function test_exception_messages_are_absent_from_http_responses_and_logs(): void
    {
        $sentinel = bin2hex(random_bytes(24));
        $stream = fopen('php://memory', 'w+');
        $this->app->make('config')->set('logging.channels.stderr.with.stream', $stream);
        Route::get('/test-failure', static function () use ($sentinel): never {
            throw new RuntimeException($sentinel);
        });
        try {
            $response = $this->getJson('/test-failure');
            self::assertFalse(str_contains($response->getContent(), $sentinel));
            rewind($stream);
            self::assertFalse(str_contains(stream_get_contents($stream), $sentinel));
            $response->assertStatus(500)->assertHeader('Content-Type', 'application/problem+json');
        } finally {
            fclose($stream);
        }
    }

    public function test_console_cannot_print_runtime_configuration(): void
    {
        $output = new BufferedOutput();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Command is incompatible with mounted runtime configuration.');
        try {
            $this->app->make(Kernel::class)->call('config:show', ['config' => 'app'], $output);
        } finally {
            self::assertFalse(str_contains($output->fetch(), $this->app->make(RuntimeConfiguration::class)->applicationKey()));
        }
    }

    public function test_safe_console_diagnostics_use_validated_environment(): void
    {
        $output = new BufferedOutput();
        self::assertSame(0, $this->app->make(Kernel::class)->call('env', ['--no-ansi' => true], $output));
        self::assertTrue(str_contains($output->fetch(), 'testing'));
    }

    public function test_http_method_errors_preserve_allow_header(): void
    {
        $this->postJson('/health/live')->assertStatus(405)->assertHeader('Allow', 'GET, HEAD')
            ->assertHeader('Content-Type', 'application/problem+json');
    }

    #[DataProvider('exceptionStatuses')]
    public function test_http_errors_keep_status_without_exposing_details(string $type, int $status): void
    {
        Route::get('/test-error', function () use ($type): never {
            throw match ($type) {
                'authentication' => new AuthenticationException(),
                'validation' => new ValidationException($this->app->make('validator')->make([], ['email' => 'required'])),
                'rate-limit' => new HttpException(429, '', headers: ['Retry-After' => '30']),
                'csrf' => new TokenMismatchException(),
            };
        });
        $response = $this->getJson('/test-error')->assertStatus($status)->assertJsonPath('status', $status)
            ->assertHeader('Content-Type', 'application/problem+json')->assertHeader('Cache-Control', 'no-store, private');
        if ($type === 'rate-limit') {
            $response->assertHeader('Retry-After', '30');
        }
    }

    public static function exceptionStatuses(): array
    {
        return ['authentication' => ['authentication', 401], 'validation' => ['validation', 422],
            'rate limit' => ['rate-limit', 429], 'csrf' => ['csrf', 419]];
    }

    public function test_bootstrap_file_consumes_only_explicit_configuration_locations(): void
    {
        $previous = $this->setLocationEnvironment();
        try {
            $application = require dirname(__DIR__, 2).'/bootstrap/app.php';
            self::assertSame($this->directory.'/writable/bootstrap/cache/config.php', $application->getCachedConfigPath());
            self::assertSame($this->directory.'/writable/storage', $application->storagePath());
            self::assertSame(dirname(__DIR__, 2).'/bootstrap/app.php', $application->bootstrapPath('app.php'));
        } finally {
            $this->restoreLocationEnvironment($previous);
        }
    }

    public function test_missing_locations_and_unusable_runtime_directories_fail_closed(): void
    {
        $previous = getenv('NATURALLY_CONFIG_PATH');
        putenv('NATURALLY_CONFIG_PATH');
        try {
            try {
                RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
                self::fail('Missing locations must fail.');
            } catch (RuntimeException $exception) {
                self::assertSame('Runtime configuration locations are required.', $exception->getMessage());
            }
        } finally {
            putenv($previous === false ? 'NATURALLY_CONFIG_PATH' : 'NATURALLY_CONFIG_PATH='.$previous);
        }
        file_put_contents($this->directory.'/occupied', 'not a directory');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Writable runtime directory is unavailable.');
        RuntimeApplicationFactory::create(dirname(__DIR__, 2), $this->directory.'/settings.json',
            $this->directory.'/secrets', $this->directory.'/occupied');
    }

    public function test_real_console_and_http_entrypoints_start_without_dotenv(): void
    {
        $console = $this->process(['php', dirname(__DIR__, 2).'/artisan', 'env', '--no-ansi']);
        $console->run();
        self::assertTrue($console->isSuccessful(), 'Console startup failed.');
        self::assertTrue(str_contains($console->getOutput(), 'testing'));
        self::assertSame('', $console->getErrorOutput());
        $routes = $this->process(['php', dirname(__DIR__, 2).'/artisan', 'route:list', '--json']);
        $routes->run();
        self::assertTrue($routes->isSuccessful(), 'Route diagnostics failed.');
        $routeList = json_decode($routes->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['health/live'], array_column($routeList, 'uri'));

        $http = $this->httpProcess();
        $http->run();
        self::assertTrue($http->isSuccessful(), 'HTTP startup failed.');
        self::assertSame(['status' => 'up'], json_decode($http->getOutput(), true, flags: JSON_THROW_ON_ERROR));
        self::assertSame('', $http->getErrorOutput());
        self::assertFalse(file_exists(dirname(__DIR__, 2).'/.env'));
    }

    public function test_entrypoints_hide_startup_failure_details(): void
    {
        unlink($this->directory.'/settings.json');
        $console = $this->process(['php', dirname(__DIR__, 2).'/artisan', 'env', '--no-ansi']);
        $console->run();
        self::assertSame(1, $console->getExitCode());
        self::assertSame('', $console->getOutput());
        self::assertSame("Runtime startup failed.\n", $console->getErrorOutput());
        $http = $this->httpProcess();
        $http->run();
        self::assertSame(1, $http->getExitCode());
        self::assertSame(['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503],
            json_decode($http->getOutput(), true, flags: JSON_THROW_ON_ERROR));
        self::assertSame("Runtime startup failed.\n", $http->getErrorOutput());
    }

    public function test_blank_location_is_rejected_without_fallback(): void
    {
        $previous = $this->setLocationEnvironment();
        putenv('NATURALLY_RUNTIME_DIRECTORY=   ');
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageIs('Runtime configuration locations are required.');
            RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
        } finally {
            $this->restoreLocationEnvironment($previous);
        }
    }

    public function test_console_restart_preserves_secret_files_and_rejects_prebuilt_cache(): void
    {
        $keyHash = hash_file('sha256', $this->directory.'/secrets/app_key');
        $passwordHash = hash_file('sha256', $this->directory.'/secrets/db_password');
        $console = $this->process(['php', dirname(__DIR__, 2).'/artisan', 'env', '--no-ansi']);
        $console->run();
        self::assertTrue($console->isSuccessful());
        self::assertTrue(hash_equals($keyHash, hash_file('sha256', $this->directory.'/secrets/app_key')));
        self::assertTrue(hash_equals($passwordHash, hash_file('sha256', $this->directory.'/secrets/db_password')));
        file_put_contents($this->directory.'/writable/bootstrap/cache/config.php', '<?php return [];');
        $restarted = $this->process(['php', dirname(__DIR__, 2).'/artisan', 'env', '--no-ansi']);
        $restarted->run();
        self::assertSame(1, $restarted->getExitCode());
        self::assertSame("Runtime startup failed.\n", $restarted->getErrorOutput());
    }

    public function test_route_cache_uses_runtime_volume_and_keeps_source_bootstrap(): void
    {
        $cache = $this->process(['php', dirname(__DIR__, 2).'/artisan', 'route:cache', '--no-ansi']);
        $cache->run();
        self::assertTrue($cache->isSuccessful(), 'Route caching failed.');
        $contents = file_get_contents($this->app->getCachedRoutesPath());
        $settings = $this->app->make(RuntimeConfiguration::class);
        self::assertFalse(str_contains($contents, $settings->applicationKey()));
        self::assertFalse(str_contains($contents, $settings->databasePassword()));
        $routes = $this->process(['php', dirname(__DIR__, 2).'/artisan', 'route:list', '--json']);
        $routes->run();
        self::assertTrue($routes->isSuccessful(), 'Cached routing failed.');
        self::assertSame(['health/live'], array_column(json_decode($routes->getOutput(), true, flags: JSON_THROW_ON_ERROR), 'uri'));
        self::assertSame(dirname(__DIR__, 2).'/bootstrap/app.php', $this->app->bootstrapPath('app.php'));
    }

    #[DataProvider('blockedCommands')]
    public function test_real_console_rejects_commands_that_expose_or_generate_secrets(array $arguments): void
    {
        $process = $this->process(['php', dirname(__DIR__, 2).'/artisan', ...$arguments, '--no-ansi']);
        $process->run();
        self::assertSame(1, $process->getExitCode());
        $output = $process->getOutput().$process->getErrorOutput();
        self::assertTrue(str_contains($output, 'Command is incompatible with mounted runtime configuration.'));
        $settings = $this->app->make(RuntimeConfiguration::class);
        self::assertFalse(str_contains($output, $settings->applicationKey()));
        self::assertFalse(str_contains($output, $settings->databasePassword()));
        self::assertFalse(file_exists(dirname(__DIR__, 2).'/.env'));
        self::assertFalse(file_exists($this->directory.'/writable/bootstrap/cache/config.php'));
    }

    public static function blockedCommands(): array
    {
        return [
            'config dump' => [['config:show', 'app']], 'config cache' => [['config:cache']],
            'key generation' => [['key:generate', '--show']], 'env encryption' => [['env:encrypt']],
            'env decryption' => [['env:decrypt']], 'optimize' => [['optimize']], 'database shell' => [['db']],
        ];
    }

    private function process(array $arguments): Process
    {
        return new Process($arguments, dirname(__DIR__, 2), [
            'NATURALLY_CONFIG_PATH' => $this->directory.'/settings.json',
            'NATURALLY_SECRET_DIRECTORY' => $this->directory.'/secrets',
            'NATURALLY_RUNTIME_DIRECTORY' => $this->directory.'/writable',
        ], timeout: 60);
    }

    private function httpProcess(): Process
    {
        $script = $this->directory.'/http.php';
        file_put_contents($script, <<<'PHP'
<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/health/live';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
require $argv[1];
PHP);
        return $this->process(['php', $script, dirname(__DIR__, 2).'/public/index.php']);
    }

    private function setLocationEnvironment(): array
    {
        $paths = ['NATURALLY_CONFIG_PATH' => $this->directory.'/settings.json',
            'NATURALLY_SECRET_DIRECTORY' => $this->directory.'/secrets',
            'NATURALLY_RUNTIME_DIRECTORY' => $this->directory.'/writable'];
        $previous = [];
        foreach ($paths as $name => $path) {
            $previous[$name] = getenv($name);
            putenv($name.'='.$path);
        }
        return $previous;
    }

    private function restoreLocationEnvironment(array $previous): void
    {
        foreach ($previous as $name => $value) {
            putenv($value === false ? $name : $name.'='.$value);
        }
    }

    private function removeFixture(string $directory): void
    {
        foreach (glob($directory.'/*') as $path) {
            if (!is_link($path) && is_dir($path)) {
                $this->removeFixture($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
