<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Shared\Infrastructure\RuntimeConfiguration;
use App\Shared\Infrastructure\LoadRuntimeEnvironment;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(LoadRuntimeEnvironment::class)]
#[CoversFile(__DIR__.'/../../config/app.php')]
#[CoversFile(__DIR__.'/../../config/database.php')]
#[UsesClass(RuntimeConfiguration::class)]
final class RuntimeEnvironmentTest extends TestCase
{
    private string $directory;
    private string $key;
    private string $password;
    private Container $previousContainer;
    private string $previousTimezone;

    protected function setUp(): void
    {
        $this->previousContainer = Container::getInstance();
        $this->previousTimezone = date_default_timezone_get();
        $this->directory = sys_get_temp_dir().'/naturally-bootstrap-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        mkdir($this->directory.'/config');
        mkdir($this->directory.'/bootstrap');
        mkdir($this->directory.'/bootstrap/cache');
        mkdir($this->directory.'/secrets', 0700);
        symlink(__DIR__.'/../../config/app.php', $this->directory.'/config/app.php');
        symlink(__DIR__.'/../../config/database.php', $this->directory.'/config/database.php');
        $this->key = 'base64:'.base64_encode(random_bytes(32));
        $this->password = bin2hex(random_bytes(24));
        file_put_contents($this->directory.'/secrets/app_key', $this->key);
        file_put_contents($this->directory.'/secrets/db_password', $this->password);
        chmod($this->directory.'/secrets/app_key', 0600);
        chmod($this->directory.'/secrets/db_password', 0600);
        file_put_contents($this->directory.'/settings.json', json_encode([
            'environment' => 'testing', 'debug' => false, 'url' => 'https://naturally.test',
            'database' => ['host' => 'db', 'port' => 3306, 'name' => 'naturally_test', 'user' => 'naturally'],
            'redis' => ['host' => 'redis', 'port' => 6379],
            'mail' => ['host' => 'mailpit', 'port' => 1025],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        date_default_timezone_set($this->previousTimezone);
        $this->removeFixture($this->directory);
    }

    public function test_laravel_consumes_validated_application_and_database_settings(): void
    {
        $application = $this->application();
        $this->bootstrap($application);
        $config = $application->make('config');

        self::assertSame('testing', $application->environment());
        self::assertSame('https://naturally.test', $config->get('app.url'));
        self::assertFalse($config->get('app.debug'));
        self::assertSame('AES-256-CBC', $config->get('app.cipher'));
        self::assertTrue(hash_equals($this->key, $config->get('app.key')));
        self::assertSame('mysql', $config->get('database.default'));
        self::assertSame('db', $config->get('database.connections.mysql.host'));
        self::assertSame(3306, $config->get('database.connections.mysql.port'));
        self::assertSame('naturally_test', $config->get('database.connections.mysql.database'));
        self::assertSame('naturally', $config->get('database.connections.mysql.username'));
        self::assertTrue(hash_equals($this->password, $config->get('database.connections.mysql.password')));
        self::assertSame('phpredis', $config->get('database.redis.client'));
        self::assertSame('naturally:testing:', $config->get('database.redis.options.prefix'));
        self::assertSame(0, $config->get('database.redis.default.database'));
        self::assertSame(1, $config->get('database.redis.cache.database'));
        self::assertSame(['mysql'], array_keys($config->get('database.connections')));
        self::assertTrue($config->get('database.connections.mysql.strict'));
        self::assertTrue($config->get('database.connections.mysql.mask_bindings_in_exception_messages'));
        self::assertFalse($config->has('queue.connections'));
        self::assertFalse($application->make('config_loaded_from_cache'));
        $encrypter = new Encrypter(base64_decode(substr($config->get('app.key'), 7), true), $config->get('app.cipher'));
        self::assertSame('runtime-round-trip', $encrypter->decryptString($encrypter->encryptString('runtime-round-trip')));
        self::assertFalse(file_exists($this->directory.'/.env'));
    }

    public function test_prebuilt_cache_cannot_replace_validated_settings(): void
    {
        file_put_contents($this->directory.'/bootstrap/cache/config.php',
            "<?php throw new LogicException('Cache must not execute.');");
        $application = $this->application();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Prebuilt runtime configuration cache is not supported.');
        try {
            $this->bootstrap($application);
        } finally {
            self::assertFalse($application->bound('config'));
            self::assertFalse($application->bound(RuntimeConfiguration::class));
        }
    }

    public function test_matching_console_environment_is_accepted(): void
    {
        $previousArguments = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', '--env', 'testing'];
        try {
            $application = $this->application();
            $this->bootstrap($application);
            self::assertSame('testing', $application->environment());
        } finally {
            $_SERVER['argv'] = $previousArguments;
        }
    }

    public function test_ambient_variables_do_not_override_validated_settings(): void
    {
        $overrides = ['APP_ENV' => 'production', 'APP_DEBUG' => 'true', 'DB_HOST' => 'ambient-db'];
        $previous = [];
        foreach ($overrides as $name => $value) {
            $previous[$name] = [getenv($name), $_ENV[$name] ?? null, $_SERVER[$name] ?? null];
            putenv($name.'='.$value);
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
        try {
            $application = $this->application();
            $this->bootstrap($application);
            self::assertSame('testing', $application->environment());
            self::assertFalse($application->make('config')->get('app.debug'));
            self::assertSame('db', $application->make('config')->get('database.connections.mysql.host'));
        } finally {
            foreach ($previous as $name => [$process, $environment, $server]) {
                putenv($process === false ? $name : $name.'='.$process);
                unset($_ENV[$name], $_SERVER[$name]);
                if ($environment !== null) {
                    $_ENV[$name] = $environment;
                }
                if ($server !== null) {
                    $_SERVER[$name] = $server;
                }
            }
        }
    }

    #[DataProvider('invalidSources')]
    public function test_invalid_sources_stop_before_laravel_configuration(string $scenario): void
    {
        $path = $this->directory.'/settings.json';
        switch ($scenario) {
            case 'missing-json':
                unlink($path);
                break;
            case 'invalid-json':
                file_put_contents($path, '{');
                break;
            case 'missing-key':
                unlink($this->directory.'/secrets/app_key');
                break;
            case 'missing-password':
                unlink($this->directory.'/secrets/db_password');
                break;
            case 'unsafe-production':
                $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
                $data['environment'] = 'production';
                $data['debug'] = true;
                file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));
                break;
        }
        $application = $this->application();
        try {
            $this->bootstrap($application);
            self::fail('Invalid runtime sources must stop bootstrap.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString($this->key, $exception->getMessage());
            self::assertStringNotContainsString($this->password, $exception->getMessage());
            self::assertFalse($application->bound('config'));
            self::assertFalse($application->bound(RuntimeConfiguration::class));
        }
    }

    public static function invalidSources(): array
    {
        return [
            'missing JSON' => ['missing-json'],
            'invalid JSON' => ['invalid-json'],
            'missing application key' => ['missing-key'],
            'missing database password' => ['missing-password'],
            'unsafe production' => ['unsafe-production'],
        ];
    }

    public function test_console_environment_cannot_override_validated_environment(): void
    {
        $previousArguments = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', '--env=production'];
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageIs('Runtime environment overrides are not supported.');
            $this->bootstrap($this->application());
        } finally {
            $_SERVER['argv'] = $previousArguments;
        }
    }

    private function application(): Application
    {
        $application = new Application($this->directory);
        $application->instance(LoadEnvironmentVariables::class, new LoadRuntimeEnvironment(
            $this->directory.'/settings.json', $this->directory.'/secrets',
        ));
        return $application;
    }

    private function bootstrap(Application $application): void
    {
        $application->bootstrapWith([LoadEnvironmentVariables::class, LoadConfiguration::class]);
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
