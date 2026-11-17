<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Process\Process;
use Tests\Support\DatabaseTestCase;

// Child-process coverage remains separate from the application report.
#[CoversNothing]
final class AccountConcurrencyTest extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';

    private function race(User $user, #[\SensitiveParameter] array $inputs): void
    {
        $db = $this->database->connection();
        $workers = [];

        try {
            $db->beginTransaction();
            $db->table('users')->where('id', $user->id)->lockForUpdate()->first();

            foreach ($inputs as $input) {
                $worker = new Process(
                    [PHP_BINARY, dirname(__DIR__).'/Support/account-worker.php'],
                    dirname(__DIR__, 2),
                    input: json_encode($input + ['prefix' => $db->getTablePrefix()], JSON_THROW_ON_ERROR),
                    timeout: 20,
                );
                $workers[] = $worker;
                $worker->start();
            }

            foreach ($workers as $worker) {
                while (!str_starts_with($worker->getOutput(), "WAITING\n") && $worker->isRunning()) {
                    $worker->checkTimeout();
                    usleep(10000);
                }
                self::assertTrue(str_starts_with($worker->getOutput(), "WAITING\n"), 'Account operation missed transaction barrier.');
            }

            $db->commit();
            $outcomes = [];
            foreach ($workers as $worker) {
                $worker->wait();
                self::assertTrue($worker->isSuccessful(), 'Account worker failed.');
                $outcomes[] = trim($worker->getOutput());
            }
            sort($outcomes);
            self::assertSame(["WAITING\nACCEPTED", "WAITING\nREJECTED"], $outcomes);
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
        }
    }

    public function testOverlappingRestorationsConsumeExactlyOneProof(): void
    {
        $user = User::factory()->create();
        $user->delete();
        $token = $this->app->make(\App\Modules\Identity\Infrastructure\RestorationTokenRepository::class)->create($user);
        $passwords = [bin2hex(random_bytes(24)), bin2hex(random_bytes(24))];
        $this->race($user, array_map(static fn ($password) => ['action' => 'restore', 'email' => $user->email, 'token' => $token, 'password' => $password], $passwords));
        $restored = User::findOrFail($user->id);
        self::assertCount(1, array_filter($passwords, fn ($password) => $this->app->make('hash')->check($password, $restored->password)));
        self::assertSame(0, $this->database->connection()->table('account_restoration_tokens')->count());
    }

    public function testEmailChangeAndDeletionCannotBothUseTheSameRevokedSession(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->app->make('auth')->guard('web')->login($user);
        $session = $this->app->make('session')->driver();
        $session->save();
        $next = 'race@example.test';
        $common = ['actor' => $user->id, 'session' => $session->getId()];
        $this->race($user, [['action' => 'email', 'email' => $next] + $common, ['action' => 'delete'] + $common]);
        $result = User::withTrashed()->findOrFail($user->id);
        self::assertNotSame($result->trashed(), $result->email === $next);
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
    }
}
