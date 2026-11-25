<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Application\AccountDeletionInput;
use App\Modules\Identity\Application\AccountRestoreInput;
use App\Modules\Identity\Application\EmailChangeInput;
use App\Modules\Identity\Application\ProfileChangeInput;
use App\Modules\Identity\Infrastructure\RestoreAccountNotification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountDeletionInput::class)]
#[CoversClass(AccountRestoreInput::class)]
#[CoversClass(EmailChangeInput::class)]
#[CoversClass(ProfileChangeInput::class)]
#[CoversClass(RestoreAccountNotification::class)]
final class AccountInputsTest extends TestCase
{
    public function testSessionAndRestorationCredentialsStayPrivate(): void
    {
        $session = bin2hex(random_bytes(20)); $token = bin2hex(random_bytes(32)); $password = bin2hex(random_bytes(24));
        $objects = [new AccountDeletionInput(1, $session), new EmailChangeInput(1, $session, 'user@example.test'), new ProfileChangeInput(1, $session, 'Name', null), new AccountRestoreInput('user@example.test', $token, $password), new RestoreAccountNotification($token)];
        foreach ($objects as $object) {
            foreach ([json_encode($object), print_r($object, true)] as $text) {
                foreach ([$session, $token, $password] as $secret) { self::assertFalse(str_contains($text, $secret)); }
            }
            try { serialize($object); self::fail('Sensitive input serialized.'); }
            catch (\LogicException) { self::assertTrue(true); }
        }
    }
}
