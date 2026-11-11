<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Application\PasswordChangeInput;
use App\Modules\Identity\Application\RegistrationInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordChangeInput::class)]
#[CoversClass(RegistrationInput::class)]
final class CredentialInputsTest extends TestCase
{
    #[DataProvider('inputTypes')]
    public function testCredentialsCannotAppearInJsonDebugOrSerialization(string $type): void
    {
        $password = bin2hex(random_bytes(24));
        $sessionId = bin2hex(random_bytes(20));
        $input = $type === 'registration' ? new RegistrationInput('Name', 'username', 'user@example.test', $password) : new PasswordChangeInput(1, $sessionId, $password);
        self::assertTrue(hash_equals($password, $input->password()));
        foreach ([json_encode($input), print_r($input, true)] as $output) {
            self::assertFalse(str_contains($output, $password));
            self::assertFalse(str_contains($output, $sessionId));
        }
        $this->expectException(\LogicException::class);
        serialize($input);
    }

    public static function inputTypes(): iterable
    {
        yield 'registration' => ['registration'];
        yield 'password change' => ['password-change'];
    }
}
