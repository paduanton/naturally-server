<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserVerificationResendTest extends BrowserTestCase
{
    private function login(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    public function testResendRequiresSessionAndCsrfAndUsesOnlyCurrentActor(): void
    {
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/email/verification-notification')->assertUnauthorized();
        $user = $this->login();
        $other = User::factory()->create();
        $this->browser('POST', '/api/v1/auth/email/verification-notification', csrf: false)->assertStatus(419);
        $this->browser('POST', '/api/v1/auth/email/verification-notification', ['user_id' => $other->id])->assertStatus(202)->assertContent('');
        $messages = $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages();
        self::assertCount(1, $messages);
        self::assertTrue(hash_equals($user->email, $messages->first()->getOriginalMessage()->getTo()[0]->getAddress()));
    }

    public function testResendHasAnAccountBudget(): void
    {
        $user = $this->login();
        for ($i = 0; $i < 6; $i++) { $this->browser('POST', '/api/v1/auth/email/verification-notification')->assertStatus(202); }
        $this->browser('POST', '/api/v1/auth/email/verification-notification')->assertStatus(429)->assertHeader('Retry-After');
        self::assertCount(6, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
    }

    public function testVerifiedUsersDoNotReceiveMoreMail(): void
    {
        $user = $this->login();
        $user->forceFill(['email_verified_at' => Date::now()])->save();
        $this->browser('POST', '/api/v1/auth/email/verification-notification')->assertStatus(202);
        self::assertCount(0, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
    }

    public function testNativeVerifiedGateProtectsFutureVerifiedOnlyResources(): void
    {
        $user = $this->login();
        Route::get('/api/v1/auth/verified-probe', static fn () => response()->json(['ok' => true]))->middleware(['identity.browser', 'auth:web', 'verified']);
        $this->browser('GET', '/api/v1/auth/verified-probe')->assertForbidden();
        $link = URL::temporarySignedRoute('identity.email.verify', Date::now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)], absolute: false);
        // Relative signatures are intentionally rejected by the absolute signed route.
        $this->browser('GET', $link)->assertForbidden();
        $link = URL::temporarySignedRoute('identity.email.verify', Date::now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->browser('GET', substr($link, strlen('https://naturally.test')))->assertNoContent();
        $this->browser('GET', '/api/v1/auth/verified-probe')->assertOk();
    }
}
