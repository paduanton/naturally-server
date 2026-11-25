<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\Username;
use App\Modules\Identity\Domain\NewPassword;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\TestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity/Domain')]
final class CredentialValuesTest extends TestCase
{
    public function testIdentifiersNormalizeWithoutProviderSpecificEmailRewrites(): void
    {
        self::assertSame('first.last+alias@example.test', EmailAddress::fromString(' First.Last+alias@Example.Test ')->value);
        self::assertSame('first.last', Username::fromString(' First.Last ')->value);
        self::assertSame('user_name', Username::fromString('USER_NAME')->value);
    }

    public function testIdentifierValidationRejectsMalformedAndOversizedValues(): void
    {
        foreach (['ab', '_user', 'user_', 'user..name', 'user_name.more', str_repeat('u', 21), "user\nname"] as $value) {
            try { Username::fromString($value); self::fail('Invalid username accepted.'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
        foreach (['missing-domain', "user@example.test\nattack", str_repeat('a', 256).'@example.test'] as $value) {
            try { EmailAddress::fromString($value); self::fail('Invalid email accepted.'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
    }

    public function testPasswordsUseCharacterBoundsAndDoNotSerialize(): void
    {
        $input = bin2hex(random_bytes(24));
        $password = NewPassword::fromString($input);
        self::assertTrue(hash_equals($input, $password->reveal()));
        self::assertSame('{}', json_encode($password));
        self::assertFalse(str_contains(print_r($password, true), $input));
        self::assertSame(15, mb_strlen(NewPassword::fromString(str_repeat('🙂', 15))->reveal()));
        foreach ([str_repeat('x', 14), str_repeat('🙂', 14), str_repeat('x', 1025), "\xff".str_repeat('x', 20)] as $invalid) {
            try { NewPassword::fromString($invalid); self::fail('Invalid password accepted.'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
        $this->expectException(\LogicException::class);
        serialize($password);
    }
}
