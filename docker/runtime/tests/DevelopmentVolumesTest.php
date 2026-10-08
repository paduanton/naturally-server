<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__).'/DevelopmentVolumes.php';

#[CoversClass(DevelopmentVolumes::class)]
#[CoversFile(__DIR__.'/../init.php')]
final class DevelopmentVolumesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/naturally-volumes-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        $this->remove($this->directory);
    }

    public function testInitializesRestrictedRandomFilesAndPreservesThemOnRestart(): void
    {
        $initializer = new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db', $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data');
        $initializer->initialize();
        self::assertFileExists($this->directory.'/app/app_key');
        self::assertFileExists($this->directory.'/app/db_password');
        self::assertFileExists($this->directory.'/db/root_password');
        $hashes = [];
        foreach (['app/app_key', 'app/db_password', 'db/db_password', 'db/root_password'] as $file) {
            $path = $this->directory.'/'.$file;
            $hashes[$file] = hash_file('sha256', $path);
            self::assertSame(0400, fileperms($path) & 0777);
            self::assertSame(str_starts_with($file, 'app/') ? 33 : 999, fileowner($path));
        }
        self::assertTrue(hash_equals($hashes['app/db_password'], $hashes['db/db_password']));
        self::assertFalse(hash_equals($hashes['db/db_password'], $hashes['db/root_password']));
        $initializer->initialize();
        foreach ($hashes as $file => $hash) {
            self::assertTrue(hash_equals($hash, hash_file('sha256', $this->directory.'/'.$file)));
        }
        foreach (['app', 'db', 'runtime', 'vendor'] as $directory) {
            self::assertSame(0700, fileperms($this->directory.'/'.$directory) & 0777);
        }
    }

    public function testConcurrentInitializersProduceOneConsistentGeneration(): void
    {
        $processes = [];
        for ($i = 0; $i < 4; $i++) {
            $process = new Process([PHP_BINARY, dirname(__DIR__).'/init.php',
                $this->directory.'/app', $this->directory.'/db', $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data']);
            $process->start();
            $processes[] = $process;
        }
        foreach ($processes as $process) {
            self::assertSame(0, $process->wait());
            self::assertSame('', $process->getErrorOutput());
        }
        self::assertTrue(hash_equals(hash_file('sha256', $this->directory.'/app/db_password'),
            hash_file('sha256', $this->directory.'/db/db_password')));
        self::assertSame([], glob($this->directory.'/app/.pending-*'));
        self::assertSame([], glob($this->directory.'/db/.pending-*'));
    }

    public function testRecoversAnInterruptedFirstGenerationWithoutRotatingItsPassword(): void
    {
        mkdir($this->directory.'/db', 0700);
        $password = bin2hex(random_bytes(32));
        file_put_contents($this->directory.'/db/db_password', $password);
        (new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db', $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data'))->initialize();
        self::assertTrue(hash_equals(hash('sha256', $password), hash_file('sha256', $this->directory.'/app/db_password')));
    }

    #[DataProvider('invalidFiles')]
    public function testRejectsDamagedInitializedVolumesWithoutRegeneratingValues(string $file, string $damage): void
    {
        $initializer = new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db', $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data');
        $initializer->initialize();
        $originalKeyHash = hash_file('sha256', $this->directory.'/app/app_key');
        $path = $this->directory.'/'.$file;
        if ($damage === 'missing') {
            unlink($path);
        } elseif ($damage === 'directory') {
            unlink($path);
            mkdir($path);
        } elseif ($damage === 'symlink') {
            unlink($path);
            symlink($this->directory.'/db/root_password', $path);
        } else {
            file_put_contents($path, $damage === 'mismatch' ? bin2hex(random_bytes(32)) : 'invalid');
        }
        try {
            $initializer->initialize();
            self::fail('Damaged secret volumes must fail closed.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString($this->directory, $exception->getMessage());
            self::assertTrue(hash_equals($originalKeyHash, hash_file('sha256', $this->directory.'/app/app_key')));
        }
    }

    public static function invalidFiles(): iterable
    {
        yield ['app/db_password', 'missing'];
        yield ['db/root_password', 'invalid'];
        yield ['db/db_password', 'mismatch'];
        yield ['app/.initialized', 'invalid'];
        yield ['app/db_password', 'symlink'];
        yield ['db/root_password', 'directory'];
        yield ['app/.init.lock', 'symlink'];
    }

    public function testRejectsASymlinkedVolumeRoot(): void
    {
        mkdir($this->directory.'/target');
        symlink($this->directory.'/target', $this->directory.'/app');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Cannot prepare development volume.');
        (new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db', $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data'))->initialize();
    }

    public function testCannotGenerateReplacementSecretsForAnExistingDatabase(): void
    {
        mkdir($this->directory.'/data/mysql', 0700, true);
        try {
            (new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db',
                $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data'))->initialize();
            self::fail('A database without its original secret volumes requires explicit recovery.');
        } catch (RuntimeException $exception) {
            self::assertSame('Existing database requires its initialized secret volumes.', $exception->getMessage());
            self::assertFileDoesNotExist($this->directory.'/app/app_key');
            self::assertFileDoesNotExist($this->directory.'/db/root_password');
        }
    }

    public function testRejectsANonCanonicalApplicationKeyWithoutChangingIt(): void
    {
        mkdir($this->directory.'/app', 0700);
        $value = 'base64:'.substr(base64_encode(random_bytes(32)), 0, -2).'B=';
        file_put_contents($this->directory.'/app/app_key', $value);
        try {
            (new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db',
                $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data'))->initialize();
            self::fail('A noncanonical application key must not be accepted or replaced.');
        } catch (RuntimeException $exception) {
            self::assertSame('Development application key is invalid.', $exception->getMessage());
            self::assertTrue(hash_equals(hash('sha256', $value), hash_file('sha256', $this->directory.'/app/app_key')));
        }
    }

    public function testAnOccupiedVolumePathFailsBeforeGeneratingSecrets(): void
    {
        file_put_contents($this->directory.'/runtime', 'occupied');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Cannot prepare development volume.');
        (new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db',
            $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data'))->initialize();
    }

    public function testAnInvalidLockPathFailsBeforeGeneratingSecrets(): void
    {
        mkdir($this->directory.'/app/.init.lock', 0700, true);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Cannot lock development initialization.');
        (new DevelopmentVolumes($this->directory.'/app', $this->directory.'/db',
            $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data'))->initialize();
    }

    public function testEntryPointSanitizesFailures(): void
    {
        mkdir($this->directory.'/app');
        $invalid = bin2hex(random_bytes(12));
        file_put_contents($this->directory.'/app/app_key', $invalid);
        $process = new Process([PHP_BINARY, dirname(__DIR__).'/init.php',
            $this->directory.'/app', $this->directory.'/db', $this->directory.'/runtime', $this->directory.'/vendor', $this->directory.'/data']);
        self::assertSame(1, $process->run());
        self::assertSame('', $process->getOutput());
        self::assertSame("Development volume initialization failed; existing values were preserved.\n", $process->getErrorOutput());
        self::assertFalse(str_contains($process->getErrorOutput(), $invalid));
    }

    private function remove(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            foreach (array_diff(scandir($path), ['.', '..']) as $entry) {
                $this->remove($path.'/'.$entry);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}
