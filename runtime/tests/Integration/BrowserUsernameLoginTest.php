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
final class BrowserUsernameLoginTest extends BrowserTestCase
{
    public function testUsernameAndEmailUseTheNativeSessionFlow(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['username' => 'user.name', 'password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['login' => ' USER.Name ', 'password' => $password])->assertNoContent();
        $this->browser('GET', '/api/v1/auth/me')->assertJsonPath('data.id', $user->id);
        $this->browser('POST', '/api/v1/auth/logout')->assertNoContent();
        $this->browser('POST', '/api/v1/auth/login', ['email' => strtoupper($user->email), 'password' => $password])->assertNoContent();
    }

    public function testIdentifiersShareTheAccountBudgetInsteadOfDoublingAttempts(): void
    {
        $user = User::factory()->create(['username' => 'user.name']);
        $this->bootstrapBrowser();
        for ($i = 0; $i < 5; $i++) {
            $this->browser('POST', '/api/v1/auth/login', ['login' => $i % 2 === 0 ? $user->email : strtoupper($user->username), 'password' => bin2hex(random_bytes(24))])->assertUnprocessable();
        }
        $this->browser('POST', '/api/v1/auth/login', ['login' => $user->username, 'password' => bin2hex(random_bytes(24))])->assertStatus(429);
    }

    public function testAmbiguousMalformedAndDeletedIdentifiersCannotAuthenticate(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['username' => 'user.name', 'password' => $password]);
        $this->bootstrapBrowser();
        foreach ([['email' => $user->email, 'login' => $user->username], ['login' => ['nested']], ['login' => str_repeat('x', 256)], ['login' => "user' OR 1=1"]] as $input) {
            $this->browser('POST', '/api/v1/auth/login', $input + ['password' => $password])->assertUnprocessable();
        }
        $user->delete();
        $this->browser('POST', '/api/v1/auth/login', ['login' => $user->username, 'password' => $password])->assertUnprocessable();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }
}
