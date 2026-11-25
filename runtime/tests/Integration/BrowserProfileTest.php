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
final class BrowserProfileTest extends BrowserTestCase
{
    private function actor(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    public function testPatchUsesSessionIdentityCsrfAndExplicitFields(): void
    {
        $this->bootstrapBrowser();
        $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Changed'])->assertUnauthorized();
        $user = $this->actor();
        $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Changed'], csrf: false)->assertStatus(419);
        $this->browser('PATCH', '/api/v1/users/me', ['name' => ' Nome 🥗 ', 'username' => ' USER.Name '])->assertNoContent()->assertHeader('Cache-Control', 'no-store, private');
        $this->browser('GET', '/api/v1/auth/me')->assertJsonPath('data.name', 'Nome 🥗')->assertJsonPath('data.username', 'user.name')->assertJsonPath('data.id', $user->id);
        foreach (['user_id', 'email', 'password', 'email_verified_at'] as $field) {
            $this->browser('PATCH', '/api/v1/users/me', [$field => 'invalid', 'name' => 'Changed'])->assertUnprocessable();
        }
        self::assertSame('Nome 🥗', $user->fresh()->name);
    }

    public function testInvalidPatchAndExpiredConfirmationCannotWrite(): void
    {
        $user = $this->actor();
        foreach ([[], ['name' => null], ['username' => 'bad..name'], ['name' => str_repeat('x', 256)]] as $input) {
            $this->browser('PATCH', '/api/v1/users/me', $input)->assertUnprocessable();
        }
        Date::setTestNow(Date::now()->addMinutes(16));
        try { $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Changed'])->assertStatus(423); }
        finally { Date::setTestNow(); }
        self::assertNotSame('Changed', $user->fresh()->name);
    }

    public function testUsernameConflictHasNoSqlOrOtherAccountDetails(): void
    {
        $user = $this->actor();
        User::factory()->create(['username' => 'taken.name']);
        $response = $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Changed', 'username' => 'taken.name'])->assertUnprocessable();
        self::assertSame(['profile' => ['The field is invalid.']], $response->json('errors'));
        self::assertNotSame('Changed', $user->fresh()->name);
    }
}
