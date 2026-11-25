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
final class BrowserRecoveryRequestTest extends BrowserTestCase
{
    public function testKnownUnknownDeletedAndSocialAccountsHaveTheSamePublicResponse(): void
    {
        $user = User::factory()->create();
        $social = User::factory()->socialOnly()->create();
        $deleted = User::factory()->create();
        $deleted->delete();
        $this->bootstrapBrowser();
        foreach ([$user->email, $social->email, $deleted->email, bin2hex(random_bytes(8)).'@example.test'] as $email) {
            $this->browser('POST', '/api/v1/auth/password/recovery', ['email' => strtoupper($email)])->assertStatus(202)->assertContent('');
        }
        $messages = $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages();
        self::assertCount(1, $messages);
        $html = $messages->first()->getOriginalMessage()->getHtmlBody();
        preg_match('~https://naturally\.test/auth/reset-password\#[^"<>\s]+~', $html, $matches);
        self::assertTrue(isset($matches[0]), 'Recovery link missing.');
        $parts = parse_url(html_entity_decode($matches[0]));
        self::assertArrayNotHasKey('query', $parts);
        parse_str($parts['fragment'], $proof);
        self::assertTrue(hash_equals($user->email, $proof['email']));
        $stored = $this->database->connection()->table('password_reset_tokens')->where('email', $user->email)->value('token');
        self::assertTrue($this->app->make('hash')->check($proof['token'], $stored));
        self::assertFalse(hash_equals($proof['token'], $stored));
    }

    public function testDeliveryThrottlesInternallyAndRequestsRequireCsrfAndBoundedEmail(): void
    {
        $user = User::factory()->create();
        $this->browser('POST', '/api/v1/auth/password/recovery', ['email' => $user->email], csrf: false)->assertStatus(419);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/password/recovery', ['email' => ['invalid']])->assertUnprocessable();
        for ($i = 0; $i < 5; $i++) { $this->browser('POST', '/api/v1/auth/password/recovery', ['email' => $user->email])->assertStatus(202); }
        self::assertCount(1, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
        $this->browser('POST', '/api/v1/auth/password/recovery', ['email' => $user->email])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function testMailConnectionFailureKeepsTheNeutralResponse(): void
    {
        $user = User::factory()->create();
        $this->app->make('config')->set('mail.default', 'smtp');
        $this->app->make('config')->set('mail.mailers.smtp', ['transport' => 'smtp', 'host' => 'redis', 'port' => 1, 'timeout' => 0.2, 'require_tls' => false]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/password/recovery', ['email' => $user->email])->assertStatus(202)->assertContent('');
        self::assertSame(1, $this->database->connection()->table('password_reset_tokens')->count());
    }
}
