<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use JsonException;
use LogicException;
use RuntimeException;
use SensitiveParameter;

final readonly class RuntimeConfiguration
{
    private const int PUBLIC_FILE_MAX_BYTES = 65536;
    private const int SECRET_FILE_MAX_BYTES = 4096;

    /**
     * @param array{host: string, port: int, name: string, user: string} $database
     * @param array{host: string, port: int} $redis
     * @param array{host: string, port: int} $mail
     */
    private function __construct(
        public string $environment,
        public bool $debug,
        public string $url,
        public array $database,
        public array $redis,
        public array $mail,
        #[SensitiveParameter] private string $applicationKey,
        #[SensitiveParameter] private string $databasePassword,
    ) {}

    public static function load(string $publicPath, string $secretDirectory): self
    {
        $json = self::readFile($publicPath, self::PUBLIC_FILE_MAX_BYTES, 'Runtime configuration is unavailable.');

        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('Runtime configuration is invalid.');
        }

        self::validate($data);

        $key = self::readSecret($secretDirectory.'/app_key');
        $encoded = substr($key, 7);
        $decoded = base64_decode($encoded, true);

        if (!str_starts_with($key, 'base64:') || $decoded === false || strlen($decoded) !== 32
            || base64_encode($decoded) !== $encoded) {
            throw new RuntimeException('Application key is invalid.');
        }

        return new self(
            $data['environment'],
            $data['debug'],
            rtrim($data['url'], '/'),
            $data['database'],
            $data['redis'],
            $data['mail'],
            $key,
            self::readSecret($secretDirectory.'/db_password'),
        );
    }

    public function applicationKey(): string
    {
        return $this->applicationKey;
    }

    public function databasePassword(): string
    {
        return $this->databasePassword;
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [
            'environment' => $this->environment,
            'debug' => $this->debug,
            'url' => $this->url,
            'database' => $this->database,
            'redis' => $this->redis,
            'mail' => $this->mail,
            'applicationKey' => '[REDACTED]',
            'databasePassword' => '[REDACTED]',
        ];
    }

    /** @return array<never, never> */
    public function __serialize(): array
    {
        throw new LogicException('Runtime configuration cannot be serialized.');
    }

    private static function validate(#[SensitiveParameter] mixed $data): void
    {
        self::requireFields($data, ['environment', 'debug', 'url', 'database', 'redis', 'mail']);

        if (!in_array($data['environment'], ['local', 'testing', 'production'], true)
            || !is_bool($data['debug'])) {
            throw new RuntimeException('Runtime configuration is invalid.');
        }

        $scheme = self::validateUrl($data['url']);

        if ($data['environment'] === 'production' && ($data['debug'] || $scheme !== 'https')) {
            throw new RuntimeException('Unsafe production configuration.');
        }

        foreach (['database', 'redis', 'mail'] as $service) {
            self::validateService($data[$service], $service === 'database');
        }
    }

    private static function validateUrl(#[SensitiveParameter] mixed $url): string
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Runtime configuration URL is invalid.');
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true) || isset($parts['user'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            throw new RuntimeException('Runtime configuration URL is invalid.');
        }

        return $scheme;
    }

    private static function validateService(#[SensitiveParameter] mixed $settings, bool $database): void
    {
        self::requireFields($settings, $database ? ['host', 'port', 'name', 'user'] : ['host', 'port']);

        if (!is_int($settings['port']) || $settings['port'] < 1 || $settings['port'] > 65535) {
            throw new RuntimeException('Service configuration is invalid.');
        }

        self::validateText($settings['host']);
        if (!filter_var($settings['host'], FILTER_VALIDATE_IP)
            && !filter_var($settings['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new RuntimeException('Service host is invalid.');
        }

        if ($database) {
            self::validateText($settings['name']);
            self::validateText($settings['user']);
        }
    }

    private static function validateText(#[SensitiveParameter] mixed $value): void
    {
        if (!is_string($value) || trim($value) === '' || trim($value) !== $value
            || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new RuntimeException('Service configuration is invalid.');
        }
    }

    /** @param list<string> $fields */
    private static function requireFields(#[SensitiveParameter] mixed $data, array $fields): void
    {
        if (!is_array($data) || array_diff($fields, array_keys($data)) !== []
            || array_diff(array_keys($data), $fields) !== []) {
            throw new RuntimeException('Runtime configuration fields are invalid.');
        }
    }

    private static function readSecret(string $path): string
    {
        $value = rtrim(self::readFile($path, self::SECRET_FILE_MAX_BYTES, 'Required runtime secret is unavailable.'), "\r\n");

        if (trim($value) === '' || strpbrk($value, "\0\r\n") !== false) {
            throw new RuntimeException('Required runtime secret is invalid.');
        }

        return $value;
    }

    private static function readFile(string $path, int $limit, string $message): string
    {
        if (!is_file($path)) {
            throw new RuntimeException($message);
        }

        // Suppress filesystem warnings: they expose paths and bypass sanitized failures.
        $content = @file_get_contents($path, false, null, 0, $limit + 1);
        if ($content === false || strlen($content) > $limit) {
            throw new RuntimeException($message);
        }

        return $content;
    }
}
