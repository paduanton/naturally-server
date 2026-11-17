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
final class BrowserEmailChangeTest extends BrowserTestCase
{
    private function actor(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password, 'email_verified_at' => Date::now()]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, $password];
    }

    public function testChangingEmailRequiresNewLoginAndNewVerification(): void
    {
        [$user, $password] = $this->actor();
        $old = $user->email;
        $this->browser('PUT', '/api/v1/users/me/email', ['email' => strtoupper($old)])->assertNoContent();
        $this->browser('GET', '/api/v1/auth/me')->assertOk()->assertJsonPath('data.email_verified', true);
        $this->browser('PUT', '/api/v1/users/me/email', ['email' => 'new@example.test'])->assertNoContent();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        self::assertSame('new@example.test', $user->fresh()->email);
        self::assertNull($user->fresh()->email_verified_at);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $old, 'password' => $password])->assertUnprocessable();
        $this->browser('POST', '/api/v1/auth/login', ['email' => 'new@example.test', 'password' => $password])->assertNoContent();
        $this->browser('GET', '/api/v1/auth/me')->assertJsonPath('data.email_verified', false);
        self::assertCount(1, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
    }

    public function testCsrfRecentProofAndFieldAllowlistProtectEmailChange(): void
    {
        [$user] = $this->actor();
        $old = $user->email;
        $this->browser('PUT', '/api/v1/users/me/email', ['email' => 'new@example.test'], csrf: false)->assertStatus(419);
        foreach ([[], ['email' => ['nested']], ['email' => 'new@example.test', 'user_id' => $user->id]] as $input) {
            $this->browser('PUT', '/api/v1/users/me/email', $input)->assertUnprocessable();
        }
        Date::setTestNow(Date::now()->addMinutes(16));
        try { $this->browser('PUT', '/api/v1/users/me/email', ['email' => 'new@example.test'])->assertStatus(423); }
        finally { Date::setTestNow(); }
        self::assertSame($old, $user->fresh()->email);
    }

    public function testUnavailableEmailUsesGenericErrorsAndRateLimit(): void
    {
        [$user] = $this->actor();
        $other = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->browser('PUT', '/api/v1/users/me/email', ['email' => $other->email])->assertUnprocessable()->assertJsonPath('errors.email', ['The field is invalid.']);
        }
        $this->browser('PUT', '/api/v1/users/me/email', ['email' => $other->email])->assertStatus(429);
        self::assertSame($user->email, $user->fresh()->email);
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
    }
}
