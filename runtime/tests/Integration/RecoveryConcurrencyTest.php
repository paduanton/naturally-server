<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Process\Process;
use Tests\Support\DatabaseTestCase;

// Child-process coverage is not aggregated into the application report.
#[CoversNothing]
final class RecoveryConcurrencyTest extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';

    public function testOverlappingConsumersAcceptExactlyOneProof(): void
    {
        $user = User::factory()->create();
        $token = $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $directory = '/run/runtime/recovery-race-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $db = $this->database->connection();
        $workers = [];
        $passwords = [];
        try {
            $db->beginTransaction();
            $db->table('users')->where('id', $user->id)->lockForUpdate()->first();
            foreach ([0, 1] as $index) {
                $passwords[$index] = bin2hex(random_bytes(24));
                $file = $directory.'/'.$index.'.json';
                file_put_contents($file, json_encode(['prefix' => $db->getTablePrefix(), 'email' => $user->email, 'token' => $token, 'password' => $passwords[$index]], JSON_THROW_ON_ERROR));
                chmod($file, 0600);
                $worker = new Process(['php', dirname(__DIR__).'/Support/recovery-worker.php', $file], dirname(__DIR__, 2), timeout: 20);
                $worker->start();
                $workers[] = $worker;
            }
            foreach ($workers as $worker) {
                while (!str_contains($worker->getOutput(), 'WAITING') && $worker->isRunning()) {
                    $worker->checkTimeout();
                    usleep(10000);
                }
                self::assertTrue(str_contains($worker->getOutput(), 'WAITING'), 'Consumer did not reach the transaction barrier.');
            }
            $db->commit();
            $accepted = 0;
            foreach ($workers as $worker) {
                $worker->wait();
                self::assertTrue($worker->isSuccessful(), 'Recovery worker failed.');
                if (str_contains($worker->getOutput(), 'ACCEPTED')) { $accepted++; }
            }
            self::assertSame(1, $accepted);
            $matches = array_filter($passwords, fn (string $password): bool => $this->app->make('hash')->check($password, $user->fresh()->password));
            self::assertCount(1, $matches);
            self::assertSame(0, $db->table('password_reset_tokens')->count());
        } finally {
            if ($db->transactionLevel() > 0) { $db->rollBack(); }
            foreach ($workers as $worker) { if ($worker->isRunning()) { $worker->stop(); } }
            foreach ([0, 1] as $index) { $file = $directory.'/'.$index.'.json'; if (is_file($file)) { unlink($file); } }
            rmdir($directory);
        }
    }
}
