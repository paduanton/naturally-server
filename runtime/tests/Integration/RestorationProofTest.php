<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use App\Modules\Identity\Infrastructure\RestorationTokenRepository;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class RestorationProofTest extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';

    public function testNativeProofsAreHashedPurposeSeparatedAndThrottled(): void
    {
        $user = User::factory()->create();
        $restore = $this->app->make(RestorationTokenRepository::class);
        $reset = $this->app->make('auth.password')->broker()->getRepository();
        $resetToken = $reset->create($user);
        $token = $restore->create($user);
        self::assertTrue($restore->exists($user, $token));
        self::assertTrue($restore->recentlyCreatedToken($user));
        self::assertFalse($restore->exists($user, $resetToken));
        self::assertFalse($reset->exists($user, $token));
        $stored = $this->database->connection()->table('account_restoration_tokens')->where('email', $user->email)->value('token');
        self::assertTrue($this->app->make('hash')->check($token, $stored));
        self::assertFalse(hash_equals($token, $stored));
        Date::setTestNow(Date::now()->addMinutes(31));
        try { self::assertFalse($restore->exists($user, $token)); }
        finally { Date::setTestNow(); }
        $restore->delete($user);
        self::assertFalse($restore->exists($user, $token));
    }

    public function testPhysicalUserRemovalCascadesPrivateProofsAndRejectsOrphans(): void
    {
        $user = User::factory()->create();
        $this->app->make(RestorationTokenRepository::class)->create($user);
        $user->forceDelete();
        self::assertSame(0, $this->database->connection()->table('account_restoration_tokens')->count());
        $this->assertSqlRejected(fn () => $this->database->connection()->table('account_restoration_tokens')->insert(['email' => 'missing@example.test', 'token' => bin2hex(random_bytes(32))]), 1452);
    }
}
