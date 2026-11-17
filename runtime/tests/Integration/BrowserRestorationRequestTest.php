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
final class BrowserRestorationRequestTest extends BrowserTestCase
{
    public function testOnlyDeletedLocalAccountReceivesProofWhileResponsesStayNeutral(): void
    {
        $active = User::factory()->create();
        $deleted = User::factory()->create(); $deleted->delete();
        $social = User::factory()->socialOnly()->create(); $social->delete();
        $this->bootstrapBrowser();
        foreach ([$active->email, $deleted->email, $social->email, 'missing@example.test'] as $email) {
            $this->browser('POST', '/api/v1/auth/account/restoration', ['email' => strtoupper($email)])->assertStatus(202)->assertContent('');
        }
        $messages = $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages();
        self::assertCount(1, $messages);
        preg_match('~https://naturally\.test/auth/restore-account\#[^"<>\s]+~', $messages->first()->getOriginalMessage()->getHtmlBody(), $matches);
        self::assertTrue(isset($matches[0]), 'Restoration link missing.');
        $parts = parse_url(html_entity_decode($matches[0]));
        self::assertArrayNotHasKey('query', $parts);
        parse_str($parts['fragment'], $proof);
        self::assertTrue(hash_equals($deleted->email, $proof['email']));
        $stored = $this->database->connection()->table('account_restoration_tokens')->where('email', $deleted->email)->value('token');
        self::assertTrue($this->app->make('hash')->check($proof['token'], $stored));
        self::assertTrue(User::withTrashed()->findOrFail($deleted->id)->trashed());
    }

    public function testCsrfShapesAndMailResendBudgetsAreEnforced(): void
    {
        $user = User::factory()->create(); $user->delete();
        $this->browser('POST', '/api/v1/auth/account/restoration', ['email' => $user->email], csrf: false)->assertStatus(419);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/account/restoration', ['email' => ['nested']])->assertUnprocessable();
        for ($i = 0; $i < 5; $i++) { $this->browser('POST', '/api/v1/auth/account/restoration', ['email' => $user->email])->assertStatus(202); }
        self::assertCount(1, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
        $this->browser('POST', '/api/v1/auth/account/restoration', ['email' => $user->email])->assertStatus(429);
    }

    public function testAuthenticatedSessionCannotRequestRestoration(): void
    {
        $password = bin2hex(random_bytes(24));
        $actor = User::factory()->create(['password' => $password]);
        $deleted = User::factory()->create();
        $deleted->delete();
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $actor->email, 'password' => $password])->assertNoContent();
        $this->browser('POST', '/api/v1/auth/account/restoration', ['email' => $deleted->email])->assertStatus(409);
        self::assertSame(0, $this->database->connection()->table('account_restoration_tokens')->count());
    }

    public function testTransportFailureKeepsNeutralResponseAndPersistedProof(): void
    {
        $user = User::factory()->create(); $user->delete();
        $this->app->make('config')->set('mail.default', 'smtp');
        $this->app->make('config')->set('mail.mailers.smtp', ['transport' => 'smtp', 'host' => 'redis', 'port' => 1, 'timeout' => 0.2, 'require_tls' => false]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/account/restoration', ['email' => $user->email])->assertStatus(202);
        self::assertSame(1, $this->database->connection()->table('account_restoration_tokens')->count());
    }
}
