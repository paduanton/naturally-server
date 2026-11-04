<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\RegisterAccount;
use App\Modules\Identity\Application\RegistrationInput;
use App\Modules\Identity\Application\RegistrationConflict;
use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class RegistrationCaseTest extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';

    public function testRegistrationNormalizesIdentifiersHashesPasswordAndReturnsOnlyAccountData(): void
    {
        $password = bin2hex(random_bytes(24));
        $result = $this->app->make(RegisterAccount::class)->handle(new RegistrationInput(' Name ', ' USER.Name ', ' User@Example.Test ', $password));
        $user = User::findOrFail($result->id);
        self::assertSame('Name', $result->name);
        self::assertSame('user.name', $result->username);
        self::assertSame('user@example.test', $result->email);
        self::assertFalse($result->verified);
        self::assertNull($user->email_verified_at);
        self::assertTrue($this->app->make('hash')->check($password, $user->password));
        self::assertSame('argon2id', password_get_info($user->password)['algoName']);
        self::assertFalse(str_contains(json_encode($result), $password));
    }

    public function testDatabaseUniquenessIsTranslatedWithoutSqlOrCredentialDetails(): void
    {
        $case = $this->app->make(RegisterAccount::class);
        $password = bin2hex(random_bytes(24));
        $case->handle(new RegistrationInput('Name', 'user.name', 'user@example.test', $password));
        try {
            $case->handle(new RegistrationInput('Other', 'other.user', 'USER@EXAMPLE.TEST', $password));
            self::fail('Duplicate account accepted.');
        } catch (RegistrationConflict $exception) {
            self::assertNull($exception->getPrevious());
            self::assertSame('Account details are unavailable.', $exception->getMessage());
        }
        self::assertSame(1, User::query()->count());
    }

    public function testInvalidDomainInputCannotWriteAnAccount(): void
    {
        try {
            $this->app->make(RegisterAccount::class)->handle(new RegistrationInput('Name', 'user.name', 'user@example.test', str_repeat('x', 14)));
            self::fail('Short password accepted.');
        } catch (\InvalidArgumentException) {
            self::assertSame(0, User::query()->count());
        }
    }

    public function testDeletedIdentityIsNotImplicitlyRestoredOrDuplicated(): void
    {
        $password = bin2hex(random_bytes(24));
        $case = $this->app->make(RegisterAccount::class);
        $created = $case->handle(new RegistrationInput('Name', 'user.name', 'user@example.test', $password));
        User::findOrFail($created->id)->delete();
        $this->expectException(RegistrationConflict::class);
        $case->handle(new RegistrationInput('Name', 'other.user', 'user@example.test', $password));
    }
}
