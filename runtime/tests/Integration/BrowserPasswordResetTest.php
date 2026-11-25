<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserPasswordResetTest extends BrowserTestCase
{
    public function testRecoveryMailCanResetCredentialsWithoutAuthenticatingTheBrowser(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        $oldCookies = $this->browserCookies;
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/password/recovery', ['email' => $user->email])->assertStatus(202);
        $html = $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages()->first()->getOriginalMessage()->getHtmlBody();
        preg_match('~https://naturally\.test/auth/reset-password\#[^"<>\s]+~', $html, $matches);
        self::assertTrue(isset($matches[0]), 'Recovery link missing.');
        parse_str(parse_url(html_entity_decode($matches[0]), PHP_URL_FRAGMENT), $proof);
        $next = bin2hex(random_bytes(24));
        $input = ['email' => $user->email, 'token' => $proof['token'], 'password' => $next, 'password_confirmation' => $next];
        $this->browser('POST', '/api/v1/auth/password/reset', $input)->assertNoContent();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        $guestCookies = $this->browserCookies;
        $this->browserCookies = $oldCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        $this->browserCookies = $guestCookies;
        $this->browser('POST', '/api/v1/auth/password/reset', $input)->assertUnprocessable();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertUnprocessable();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $next])->assertNoContent();
    }

    public function testCsrfBoundsConfirmationAndGuestRequirementProtectReset(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $token = $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $next = bin2hex(random_bytes(24));
        $input = ['email' => $user->email, 'token' => $token, 'password' => $next, 'password_confirmation' => $next];
        $this->browser('POST', '/api/v1/auth/password/reset', $input, csrf: false)->assertStatus(419);
        $this->bootstrapBrowser();
        foreach ([['password_confirmation' => 'invalid'], ['password' => str_repeat('x', 14)], ['token' => ['invalid']]] as $override) {
            $this->browser('POST', '/api/v1/auth/password/reset', array_replace($input, $override))->assertUnprocessable();
        }
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        $this->browser('POST', '/api/v1/auth/password/reset', $input)->assertStatus(409);
        self::assertTrue($this->app->make('hash')->check($password, $user->fresh()->password));
    }

    public function testInvalidProofsHaveTheSameFailureAndAreRateLimited(): void
    {
        $user = User::factory()->create();
        $next = bin2hex(random_bytes(24));
        $this->bootstrapBrowser();
        $errors = null;
        for ($i = 0; $i < 10; $i++) {
            $email = $i % 2 === 0 ? $user->email : bin2hex(random_bytes(8)).'@example.test';
            $response = $this->browser('POST', '/api/v1/auth/password/reset', ['email' => $email, 'token' => bin2hex(random_bytes(32)), 'password' => $next, 'password_confirmation' => $next])->assertUnprocessable();
            $errors ??= $response->json('errors');
            self::assertSame($errors, $response->json('errors'));
        }
        $this->browser('POST', '/api/v1/auth/password/reset', ['email' => $user->email, 'token' => bin2hex(random_bytes(32)), 'password' => $next, 'password_confirmation' => $next])->assertStatus(429)->assertHeader('Retry-After');
    }
}
