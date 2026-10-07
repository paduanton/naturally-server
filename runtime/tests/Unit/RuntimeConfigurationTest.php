<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Shared\Infrastructure\RuntimeConfiguration;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(RuntimeConfiguration::class)]
final class RuntimeConfigurationTest extends TestCase
{
    private string $directory;
    private string $publicPath;
    private string $key;
    private string $password;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/naturally-config-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $this->publicPath = $this->directory.'/config.json';
        $this->key = 'base64:'.base64_encode(random_bytes(32));
        $this->password = bin2hex(random_bytes(24));
        $this->writeSettings($this->settings());
        $this->write('app_key', $this->key);
        $this->write('db_password', $this->password);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') as $path) {
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                rmdir($path);
            } else {
                chmod($path, 0600);
                unlink($path);
            }
        }

        rmdir($this->directory);
    }

    public function test_loads_public_settings_and_secrets(): void
    {
        $config = $this->load();

        self::assertSame('testing', $config->environment);
        self::assertFalse($config->debug);
        self::assertSame('https://naturally.test', $config->url);
        self::assertSame(['host' => 'db', 'port' => 3306, 'name' => 'naturally_test', 'user' => 'naturally'], $config->database);
        self::assertSame(['host' => 'redis', 'port' => 6379], $config->redis);
        self::assertSame(['host' => 'mailpit', 'port' => 1025], $config->mail);
        self::assertTrue(hash_equals($this->key, $config->applicationKey()));
        self::assertTrue(hash_equals($this->password, $config->databasePassword()));
    }

    #[DataProvider('validEnvironments')]
    public function test_loads_allowed_environments(string $environment, bool $debug, string $url): void
    {
        $data = $this->settings();
        $data['environment'] = $environment;
        $data['debug'] = $debug;
        $data['url'] = $url;
        $this->writeSettings($data);

        $config = $this->load();

        self::assertSame($environment, $config->environment);
        self::assertSame($debug, $config->debug);
        self::assertSame(rtrim($url, '/'), $config->url);
    }

    public static function validEnvironments(): iterable
    {
        yield 'local debugging' => ['local', true, 'http://localhost:8080/'];
        yield 'testing' => ['testing', false, 'http://localhost:8080'];
        yield 'production' => ['production', false, 'https://naturally.test/'];
        yield 'case insensitive HTTPS' => ['production', false, 'HTTPS://naturally.test'];
    }

    #[DataProvider('invalidDocuments')]
    public function test_rejects_invalid_json_documents(string $json): void
    {
        $this->write('config.json', $json);
        $this->expectException(RuntimeException::class);
        $this->load();
    }

    public static function invalidDocuments(): iterable
    {
        foreach (['', '{', 'null', '[]', 'true', '42', '"text"', "\xFF"] as $document) {
            yield [$document];
        }
    }

    #[DataProvider('missingFields')]
    public function test_requires_every_public_field(array $path): void
    {
        $data = $this->settings();
        $parent = &$data;
        foreach (array_slice($path, 0, -1) as $field) {
            $parent = &$parent[$field];
        }
        unset($parent[$path[array_key_last($path)]]);
        $this->writeSettings($data);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Runtime configuration fields are invalid.');
        $this->load();
    }

    public static function missingFields(): iterable
    {
        foreach (['environment', 'debug', 'url', 'database', 'redis', 'mail'] as $field) {
            yield $field => [[$field]];
        }
        foreach (['database' => ['host', 'port', 'name', 'user'], 'redis' => ['host', 'port'], 'mail' => ['host', 'port']] as $service => $fields) {
            foreach ($fields as $field) {
                yield $service.'.'.$field => [[$service, $field]];
            }
        }
    }

    #[DataProvider('unknownFields')]
    public function test_rejects_unknown_fields_including_secrets(array $path): void
    {
        $this->change($path, $this->password);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Runtime configuration fields are invalid.');
        $this->load();
    }

    public static function unknownFields(): iterable
    {
        yield 'unknown root field' => [['unexpected']];
        yield 'application key in JSON' => [['app_key']];
        yield 'password at root' => [['password']];
        foreach (['database', 'redis', 'mail'] as $service) {
            yield $service.' password' => [[$service, 'password']];
        }
    }

    #[DataProvider('invalidFields')]
    public function test_rejects_invalid_field_types_and_values(array $path, mixed $value): void
    {
        $this->change($path, $value);
        $this->expectException(RuntimeException::class);
        $this->load();
    }

    public static function invalidFields(): iterable
    {
        yield 'environment number' => [['environment'], 42];
        yield 'unknown environment' => [['environment'], 'unknown'];
        yield 'environment array' => [['environment'], []];
        yield 'debug string' => [['debug'], 'false'];
        yield 'debug integer' => [['debug'], 0];
        yield 'URL number' => [['url'], 42];
        yield 'URL malformed' => [['url'], 'relative/path'];
        yield 'non HTTP URL' => [['url'], 'ftp://naturally.test'];
        yield 'URL userinfo' => [['url'], 'https://user@naturally.test'];
        yield 'URL query' => [['url'], 'https://naturally.test?debug=1'];
        yield 'URL fragment' => [['url'], 'https://naturally.test#section'];
        yield 'URL empty query' => [['url'], 'https://naturally.test?'];
        yield 'URL empty fragment' => [['url'], 'https://naturally.test#'];
        foreach (['database', 'redis', 'mail'] as $service) {
            yield $service.' section type' => [[$service], false];
            yield $service.' host type' => [[$service, 'host'], 42];
            yield $service.' blank host' => [[$service, 'host'], ''];
            yield $service.' blank spaces' => [[$service, 'host'], '  '];
            yield $service.' surrounding whitespace' => [[$service, 'host'], ' host '];
            yield $service.' control character' => [[$service, 'host'], "host\nother"];
            yield $service.' host URI' => [[$service, 'host'], 'tcp://host'];
            yield $service.' host with path' => [[$service, 'host'], 'host/path'];
            foreach (['string' => '3306', 'zero' => 0, 'negative' => -1, 'too high' => 65536, 'fraction' => 1.5, 'boolean' => true] as $case => $port) {
                yield $service.' port '.$case => [[$service, 'port'], $port];
            }
        }
        yield 'database name type' => [['database', 'name'], []];
        yield 'database name empty' => [['database', 'name'], ''];
        yield 'database user null' => [['database', 'user'], null];
        yield 'database user whitespace' => [['database', 'user'], ' '];
    }

    #[DataProvider('productionFailures')]
    public function test_rejects_unsafe_production_configuration(bool $debug, string $url): void
    {
        $data = $this->settings();
        $data['environment'] = 'production';
        $data['debug'] = $debug;
        $data['url'] = $url;
        $this->writeSettings($data);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Unsafe production configuration.');
        $this->load();
    }

    public static function productionFailures(): iterable
    {
        yield 'debug enabled' => [true, 'https://naturally.test'];
        yield 'plain HTTP' => [false, 'http://naturally.test'];
    }

    #[DataProvider('validPorts')]
    public function test_accepts_port_boundaries(string $service, int $port): void
    {
        $this->change([$service, 'port'], $port);
        self::assertSame($port, $this->load()->{$service}['port']);
    }

    public static function validPorts(): iterable
    {
        foreach (['database', 'redis', 'mail'] as $service) {
            yield $service.' minimum' => [$service, 1];
            yield $service.' maximum' => [$service, 65535];
        }
    }

    #[DataProvider('validHosts')]
    public function test_accepts_dns_names_and_ip_addresses(string $host): void
    {
        $data = $this->settings();
        foreach (['database', 'redis', 'mail'] as $service) {
            $data[$service]['host'] = $host;
        }
        $this->writeSettings($data);
        $config = $this->load();

        self::assertSame($host, $config->database['host']);
        self::assertSame($host, $config->redis['host']);
        self::assertSame($host, $config->mail['host']);
    }

    public static function validHosts(): iterable
    {
        foreach (['localhost', 'db.namespace.svc.cluster.local', '127.0.0.1', '::1'] as $host) {
            yield [$host];
        }
    }

    public function test_url_credentials_are_rejected_without_exposing_values(): void
    {
        $this->change(['url'], 'https://user:'.$this->password.'@naturally.test');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Runtime configuration URL is invalid.');
        $this->load();
    }

    #[DataProvider('unavailableFiles')]
    public function test_fails_closed_for_unavailable_files(string $file, string $mode): void
    {
        $path = $this->directory.'/'.$file;
        if ($mode === 'unreadable') {
            chmod($path, 0000);
        } else {
            unlink($path);
            if ($mode === 'directory') {
                mkdir($path);
            }
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs($file === 'config.json'
            ? 'Runtime configuration is unavailable.' : 'Required runtime secret is unavailable.');
        $this->load();
    }

    public static function unavailableFiles(): iterable
    {
        foreach (['config.json', 'app_key', 'db_password'] as $file) {
            foreach (['missing', 'directory', 'unreadable'] as $mode) {
                yield $file.' '.$mode => [$file, $mode];
            }
        }
    }

    #[DataProvider('invalidSecrets')]
    public function test_rejects_empty_or_multiline_secrets(string $file, string $mode): void
    {
        $value = match ($mode) {
            'empty' => '',
            'whitespace' => " \t\r\n",
            'NUL' => $this->password."\0",
            'newline' => $this->password."\n".$this->password,
            'CR' => $this->password."\r".$this->password,
        };
        $this->write($file, $value);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Required runtime secret is invalid.');
        $this->load();
    }

    public static function invalidSecrets(): iterable
    {
        foreach (['app_key', 'db_password'] as $file) {
            foreach (['empty', 'whitespace', 'NUL', 'newline', 'CR'] as $mode) {
                yield $file.' '.$mode => [$file, $mode];
            }
        }
    }

    #[DataProvider('invalidKeys')]
    public function test_rejects_malformed_application_keys(string $mode): void
    {
        $encoded = base64_encode(random_bytes(32));
        $value = match ($mode) {
            'prefix' => 'other:'.$encoded,
            'alphabet' => 'base64:'.str_repeat('!', 44),
            'short' => 'base64:'.base64_encode(random_bytes(31)),
            'long' => 'base64:'.base64_encode(random_bytes(33)),
            'padding' => 'base64:'.rtrim($encoded, '='),
            'whitespace' => 'base64:'.substr($encoded, 0, 5).' '.substr($encoded, 5),
        };
        $this->write('app_key', $value);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Application key is invalid.');
        $this->load();
    }

    public static function invalidKeys(): iterable
    {
        foreach (['prefix', 'alphabet', 'short', 'long', 'padding', 'whitespace'] as $mode) {
            yield $mode => [$mode];
        }
    }

    public function test_reads_newline_terminated_secrets_without_trimming_password_spaces(): void
    {
        $password = ' '.$this->password.' ';
        $this->write('app_key', $this->key."\n");
        $this->write('db_password', $password."\r\n");
        $config = $this->load();

        self::assertTrue(hash_equals($this->key, $config->applicationKey()));
        self::assertTrue(hash_equals($password, $config->databasePassword()));
    }

    public function test_limits_configuration_file_size(): void
    {
        $json = json_encode($this->settings(), JSON_THROW_ON_ERROR);
        $this->write('config.json', str_pad($json, 64 * 1024, ' '));
        self::assertSame('testing', $this->load()->environment);

        $this->write('config.json', str_pad($json, 64 * 1024 + 1, ' '));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Runtime configuration is unavailable.');
        $this->load();
    }

    public function test_limits_secret_file_size(): void
    {
        $password = str_repeat($this->password, 86);
        $this->write('db_password', substr($password, 0, 4096));
        self::assertSame(4096, strlen($this->load()->databasePassword()));

        $this->write('db_password', substr($password, 0, 4097));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Required runtime secret is unavailable.');
        $this->load();
    }

    public function test_reads_symlink_mounts_and_refreshes_only_on_new_load(): void
    {
        $nextKey = 'base64:'.base64_encode(random_bytes(32));
        $this->write('key-version-1', $this->key);
        $this->write('key-version-2', $nextKey);
        unlink($this->directory.'/app_key');
        symlink($this->directory.'/key-version-1', $this->directory.'/app_key');
        $before = $this->load();

        unlink($this->directory.'/app_key');
        symlink($this->directory.'/key-version-2', $this->directory.'/app_key');
        $after = $this->load();

        self::assertTrue(hash_equals($this->key, $before->applicationKey()));
        self::assertTrue(hash_equals($nextKey, $after->applicationKey()));
    }

    public function test_inspecting_configuration_does_not_expose_secrets(): void
    {
        $config = $this->load();
        ob_start();
        var_dump($config);
        $dump = ob_get_clean();
        $views = [$dump, print_r($config, true), json_encode($config, JSON_THROW_ON_ERROR)];

        foreach ($views as $view) {
            self::assertFalse(str_contains($view, $this->key));
            self::assertFalse(str_contains($view, $this->password));
        }
    }

    public function test_serialization_cannot_persist_secrets(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs('Runtime configuration cannot be serialized.');
        serialize($this->load());
    }

    public function test_errors_and_traces_do_not_disclose_rejected_json_values(): void
    {
        $this->change(['database', 'password'], $this->password);
        $previous = ini_set('zend.exception_ignore_args', '0');

        try {
            try {
                $this->load();
                self::fail('An embedded secret was accepted.');
            } catch (RuntimeException $exception) {
                self::assertSame('Runtime configuration fields are invalid.', $exception->getMessage());
                self::assertNull($exception->getPrevious());
                $trace = json_encode($exception->getTrace(), JSON_THROW_ON_ERROR);
                self::assertFalse(str_contains($trace, $this->password));
            }
        } finally {
            ini_set('zend.exception_ignore_args', $previous);
        }
    }

    private function load(): RuntimeConfiguration
    {
        return RuntimeConfiguration::load($this->publicPath, $this->directory);
    }

    private function change(array $path, mixed $value): void
    {
        $data = $this->settings();
        $target = &$data;
        foreach ($path as $field) {
            $target = &$target[$field];
        }
        $target = $value;
        $this->writeSettings($data);
    }

    private function settings(): array
    {
        return [
            'environment' => 'testing', 'debug' => false, 'url' => 'https://naturally.test',
            'database' => ['host' => 'db', 'port' => 3306, 'name' => 'naturally_test', 'user' => 'naturally'],
            'redis' => ['host' => 'redis', 'port' => 6379],
            'mail' => ['host' => 'mailpit', 'port' => 1025],
        ];
    }

    private function writeSettings(array $data): void
    {
        $this->write('config.json', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function write(string $name, string $value): void
    {
        file_put_contents($this->directory.'/'.$name, $value);
        chmod($this->directory.'/'.$name, 0600);
    }
}
