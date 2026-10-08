<?php

declare(strict_types=1);

final readonly class DevelopmentVolumes
{
    public function __construct(
        private string $applicationDirectory,
        private string $databaseDirectory,
        private string $runtimeDirectory,
        private string $dependencyDirectory,
        private string $databaseDataDirectory,
    ) {}

    public function initialize(): void
    {
        $this->directory($this->applicationDirectory, 33);
        $this->directory($this->databaseDirectory, 999);
        $this->directory($this->runtimeDirectory, 33);
        $this->directory($this->dependencyDirectory, 33);
        $lockPath = $this->applicationDirectory.'/.init.lock';
        if (is_link($lockPath)) {
            throw new RuntimeException('Invalid initialization lock.');
        }
        $lock = @fopen($lockPath, 'c');
        if ($lock === false || !@chmod($lockPath, 0600) || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Cannot lock development initialization.');
        }

        try {
            $this->secrets();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function secrets(): void
    {
        $app = $this->applicationDirectory;
        $db = $this->databaseDirectory;
        $appMarker = $this->read($app.'/.initialized', '/\A1\n\z/D');
        $dbMarker = $this->read($db.'/.initialized', '/\A1\n\z/D');
        if (is_dir($this->databaseDataDirectory.'/mysql') && ($appMarker === null || $dbMarker === null)) {
            throw new RuntimeException('Existing database requires its initialized secret volumes.');
        }
        $key = $this->read($app.'/app_key', '/\Abase64:[A-Za-z0-9+\/]{43}=\z/D');
        $appPassword = $this->read($app.'/db_password', '/\A[a-f0-9]{64}\z/D');
        $dbPassword = $this->read($db.'/db_password', '/\A[a-f0-9]{64}\z/D');
        $rootPassword = $this->read($db.'/root_password', '/\A[a-f0-9]{64}\z/D');

        if (($appMarker !== null || $dbMarker !== null)
            && ($key === null || $appPassword === null || $dbPassword === null || $rootPassword === null)) {
            throw new RuntimeException('Initialized secret volume is incomplete; explicit recovery is required.');
        }
        if ($appPassword !== null && $dbPassword !== null && !hash_equals($appPassword, $dbPassword)) {
            throw new RuntimeException('Development secret volumes are inconsistent.');
        }
        if ($key !== null && base64_encode(base64_decode(substr($key, 7), true)) !== substr($key, 7)) {
            throw new RuntimeException('Development application key is invalid.');
        }

        $key ??= 'base64:'.base64_encode(random_bytes(32));
        $password = $appPassword ?? $dbPassword ?? bin2hex(random_bytes(32));
        $rootPassword ??= bin2hex(random_bytes(32));
        $this->persist($app.'/app_key', $key, 33);
        $this->persist($app.'/db_password', $password, 33);
        $this->persist($db.'/db_password', $password, 999);
        $this->persist($db.'/root_password', $rootPassword, 999);
        $this->persist($db.'/.initialized', "1\n", 0);
        $this->persist($app.'/.initialized', "1\n", 0);
    }

    private function directory(string $path, int $owner): void
    {
        if (is_link($path) || (!is_dir($path) && !@mkdir($path, 0700, true) && !is_dir($path))
            || !@chown($path, $owner) || !@chmod($path, 0700)) {
            throw new RuntimeException('Cannot prepare development volume.');
        }
    }

    private function read(string $path, string $pattern): ?string
    {
        if (is_link($path) || (file_exists($path) && !is_file($path))) {
            throw new RuntimeException('Invalid development secret file.');
        }
        if (!file_exists($path)) {
            return null;
        }
        $value = @file_get_contents($path, false, null, 0, 4097);
        if ($value === false || preg_match($pattern, $value) !== 1) {
            throw new RuntimeException('Invalid development secret file.');
        }

        return $value;
    }

    private function persist(string $path, #[SensitiveParameter] string $value, int $owner): void
    {
        if (is_file($path)) {
            if (!@chown($path, $owner) || !@chmod($path, 0400)) {
                throw new RuntimeException('Cannot restrict development secret file.');
            }
            return;
        }
        $temporary = @tempnam(dirname($path), '.pending-');
        if ($temporary === false) {
            throw new RuntimeException('Cannot prepare development secret file.');
        }
        try {
            $handle = @fopen($temporary, 'wb');
            if ($handle === false) {
                throw new RuntimeException('Cannot write development secret file.');
            }
            try {
                if (fwrite($handle, $value) !== strlen($value) || !fsync($handle)
                    || !@chown($temporary, $owner) || !@chmod($temporary, 0400)) {
                    throw new RuntimeException('Cannot write development secret file.');
                }
            } finally {
                fclose($handle);
            }
            if (!@rename($temporary, $path)) {
                throw new RuntimeException('Cannot publish development secret file.');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
