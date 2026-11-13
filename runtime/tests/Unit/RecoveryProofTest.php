<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\RecoveryToken;
use App\Modules\Identity\Application\PasswordResetInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecoveryToken::class)]
#[CoversClass(PasswordResetInput::class)]
final class RecoveryProofTest extends TestCase
{
    public function testProofsRemainPrivateAndRejectSerialization(): void
    {
        $token = bin2hex(random_bytes(32));
        $password = bin2hex(random_bytes(24));
        $proof = RecoveryToken::fromString($token);
        $input = new PasswordResetInput('user@example.test', $token, $password);
        self::assertTrue(hash_equals($token, $proof->reveal()));
        self::assertTrue(hash_equals($token, $input->token()));
        self::assertTrue(hash_equals($password, $input->password()));
        foreach ([$proof, $input] as $object) {
            foreach ([json_encode($object), print_r($object, true)] as $text) {
                self::assertFalse(str_contains($text, $token));
                self::assertFalse(str_contains($text, $password));
            }
            try { serialize($object); self::fail('Recovery credentials serialized.'); }
            catch (\LogicException) { self::assertTrue(true); }
        }
    }

    public function testOnlyTheNativeOpaqueTokenFormatIsAccepted(): void
    {
        foreach (['', bin2hex(random_bytes(31)), bin2hex(random_bytes(33)), 'g'.bin2hex(random_bytes(32))] as $invalid) {
            try { RecoveryToken::fromString($invalid); self::fail('Invalid recovery proof accepted.'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
    }
}
