<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\Phone;
use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Process\Process;
use Tests\Support\DatabaseTestCase;

// Separate PHP/PDO workers exercise real locking; subprocess coverage is not aggregated.
#[CoversNothing]
final class PhoneContactConcurrencyTest extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';

    public function testConcurrentSecondaryAdditionsCannotExceedTwoActiveContacts(): void
    {
        $user = User::factory()->create();
        Phone::factory()->forUser($user->id)->asMain()->create(['number' => '+5511999990001']);
        $this->app->make('auth')->guard('web')->login($user);
        $session = $this->app->make('session')->driver();
        $session->save();
        $db = $this->database->connection();
        $workers = [];

        try {
            $db->beginTransaction();
            $db->table('users')->where('id', $user->id)->lockForUpdate()->first();
            foreach (['+5511999990002', '+5511999990003'] as $number) {
                $worker = new Process(
                    [PHP_BINARY, dirname(__DIR__).'/Support/phone-worker.php'],
                    dirname(__DIR__, 2),
                    input: json_encode([
                        'actor' => $user->id,
                        'session' => $session->getId(),
                        'number' => $number,
                        'prefix' => $db->getTablePrefix(),
                    ], JSON_THROW_ON_ERROR),
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
                self::assertTrue(str_starts_with($worker->getOutput(), "WAITING\n"), 'Phone addition missed transaction barrier.');
            }
            $db->commit();
            $results = [];
            foreach ($workers as $worker) {
                $worker->wait();
                self::assertTrue($worker->isSuccessful(), 'Phone worker failed.');
                $results[] = trim($worker->getOutput());
            }
            sort($results);
            self::assertSame(["WAITING\nACCEPTED", "WAITING\nREJECTED"], $results);
            self::assertSame(2, Phone::where('user_id', $user->id)->count());
            self::assertSame(1, Phone::where('user_id', $user->id)->where('main', true)->count());
            self::assertSame(1, Phone::where('user_id', $user->id)->where('main', false)->count());
        } finally {
            if ($db->transactionLevel() > 0) { $db->rollBack(); }
            foreach ($workers as $worker) { if ($worker->isRunning()) { $worker->stop(); } }
        }
    }
}
