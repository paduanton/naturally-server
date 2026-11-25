<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BirthdayProfileTest extends BrowserTestCase
{
    private function actor(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    public function testBirthdayIsPrivateOptionalAndCanBeChangedOrClearedWithoutChangingOtherFields(): void
    {
        $user = $this->actor();
        $other = User::factory()->create(['birthday' => '1990-01-01']);
        $this->browser('GET', '/api/v1/auth/me')->assertJsonPath('data.birthday', null);
        $this->browser('PATCH', '/api/v1/users/me', ['birthday' => '2000-02-29'])->assertNoContent();
        self::assertSame('2000-02-29', $user->fresh()->birthday->format('Y-m-d'));
        $this->browser('GET', '/api/v1/auth/me')->assertJsonPath('data.birthday', '2000-02-29')->assertHeader('Cache-Control', 'no-store, private');
        $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Changed'])->assertNoContent();
        self::assertSame('2000-02-29', $user->fresh()->birthday->format('Y-m-d'));
        $this->browser('PATCH', '/api/v1/users/me', ['birthday' => null])->assertNoContent();
        self::assertNull($user->fresh()->birthday);
        self::assertSame('Changed', $user->fresh()->name);
        self::assertSame('1990-01-01', $other->fresh()->birthday->format('Y-m-d'));
    }

    public function testCalendarAgeBoundaryAndInvalidDatesDoNotPartiallyChangeProfile(): void
    {
        Date::setTestNow('2026-10-14 12:00:00 UTC');
        try {
            $user = $this->actor();
            foreach (['2019-10-15', '2026-10-15', '1920-01-01', '2001-02-29', '2000/02/29'] as $birthday) {
                $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Changed', 'birthday' => $birthday])->assertUnprocessable();
                self::assertSame($user->name, $user->fresh()->name);
                self::assertNull($user->fresh()->birthday);
            }
            $this->browser('PATCH', '/api/v1/users/me', ['birthday' => '2019-10-14'])->assertNoContent();
            self::assertSame('2019-10-14', $user->fresh()->birthday->format('Y-m-d'));
        } finally { Date::setTestNow(); }
    }

    public function testBirthdayPatchRetainsCsrfAndRecentAuthenticationRequirements(): void
    {
        $user = $this->actor();
        $this->browser('PATCH', '/api/v1/users/me', ['birthday' => '2000-01-01'], csrf: false)->assertStatus(419);
        Date::setTestNow(Date::now()->addMinutes(16));
        try { $this->browser('PATCH', '/api/v1/users/me', ['birthday' => '2000-01-01'])->assertStatus(423); }
        finally { Date::setTestNow(); }
        self::assertNull($user->fresh()->birthday);
    }

    public function testUsernameConflictRollsBackBirthdayAndNameTogether(): void
    {
        $user = $this->actor();
        User::factory()->create(['username' => 'taken.name']);
        $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Changed', 'username' => 'taken.name', 'birthday' => '2000-01-01'])->assertUnprocessable();
        self::assertSame($user->name, $user->fresh()->name);
        self::assertNull($user->fresh()->birthday);
    }
}
