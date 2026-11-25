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
final class BrowserRegistrationTest extends BrowserTestCase
{
    private function input(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $password = bin2hex(random_bytes(24));
        return ['name' => 'Synthetic Name', 'username' => 'user'.$suffix, 'email' => $suffix.'@example.test', 'password' => $password, 'password_confirmation' => $password];
    }

    public function testBrowserCanRegisterWithoutReceivingCredentials(): void
    {
        $this->bootstrapBrowser();
        $input = $this->input();
        $response = $this->browser('POST', '/api/v1/auth/register', $input)->assertCreated();
        $user = User::query()->sole();
        $response->assertExactJson(['data' => ['id' => $user->id]]);
        self::assertTrue($this->app->make('hash')->check($input['password'], $user->password));
        self::assertNull($user->email_verified_at);
    }

    public function testMissingCsrfAndForgedProtectedFieldsCannotCreateAnAccount(): void
    {
        $this->browser('POST', '/api/v1/auth/register', $this->input(), csrf: false)->assertStatus(419);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/register', $this->input() + ['id' => 99, 'email_verified_at' => '2020-01-01', 'remember' => true])->assertUnprocessable();
        self::assertSame(0, User::query()->count());
    }

    public function testInvalidPasswordAndDuplicateIdentityReturnSafeProblems(): void
    {
        $this->bootstrapBrowser();
        $input = $this->input();
        $this->browser('POST', '/api/v1/auth/register', array_replace($input, ['password' => str_repeat('x', 14)]))->assertUnprocessable()->assertJsonPath('code', 'validation_failed');
        $this->browser('POST', '/api/v1/auth/register', $input)->assertCreated();
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/register', $input)->assertUnprocessable();
        self::assertSame(1, User::query()->count());
    }
}
