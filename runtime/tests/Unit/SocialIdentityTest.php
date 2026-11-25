<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\OAuthPurpose;
use App\Modules\Identity\Domain\SocialIdentity;
use App\Modules\Identity\Domain\SocialProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SocialIdentity::class)]
#[CoversClass(OAuthPurpose::class)]
#[UsesClass(EmailAddress::class)]
final class SocialIdentityTest extends TestCase
{
    public function testSubjectIsOpaqueAndCaseSensitiveWithinTheStorageLimit(): void
    {
        $upper = SocialIdentity::fromProvider(SocialProvider::Google, '000A_01', null, false);
        $lower = SocialIdentity::fromProvider(SocialProvider::Google, '000a_01', null, false);
        $longSubject = str_repeat('é', 191);
        $long = SocialIdentity::fromProvider(SocialProvider::X, $longSubject, null, false);

        self::assertSame('000A_01', $upper->subject());
        self::assertSame('000a_01', $lower->subject());
        self::assertSame($longSubject, $long->subject());
        self::assertSame(SocialProvider::X, $long->provider);
    }

    #[DataProvider('emailEvidence')]
    public function testEmailVerificationRequiresAnAuthoritativeGoogleDecision(
        SocialProvider $provider,
        ?string $email,
        bool $authoritative,
        bool $expected,
    ): void {
        $identity = SocialIdentity::fromProvider($provider, 'subject', $email, $authoritative);

        self::assertSame($expected, $identity->emailVerified);
        self::assertSame($email === null ? null : strtolower($email), $identity->email()?->value);
    }

    public static function emailEvidence(): iterable
    {
        yield 'authoritative Google email' => [SocialProvider::Google, 'User@example.test', true, true];
        yield 'non-authoritative Google email' => [SocialProvider::Google, 'User@example.test', false, false];
        yield 'absent Google email' => [SocialProvider::Google, null, true, false];
        yield 'Facebook email' => [SocialProvider::Facebook, 'User@example.test', true, false];
        yield 'X email' => [SocialProvider::X, 'User@example.test', true, false];
    }

    #[DataProvider('invalidSubjects')]
    public function testRejectsInvalidSubjects(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SocialIdentity::fromProvider(SocialProvider::Google, $value, null, false);
    }

    public static function invalidSubjects(): iterable
    {
        yield 'empty' => [''];
        yield 'leading space' => [' leading'];
        yield 'trailing space' => ['trailing '];
        yield 'control character' => ["bad\nsubject"];
        yield 'too many characters' => [str_repeat('é', 192)];
        yield 'invalid UTF-8' => ["bad\xFFsubject"];
    }

    public function testDebugOutputAndJsonDoNotExposeSubjectOrEmail(): void
    {
        $subject = bin2hex(random_bytes(16));
        $email = 'private@example.test';
        $identity = SocialIdentity::fromProvider(SocialProvider::Google, $subject, $email, true);

        ob_start();
        try {
            var_dump($identity);
            $debug = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        foreach ([$debug, json_encode($identity, JSON_THROW_ON_ERROR)] as $output) {
            self::assertStringNotContainsString($subject, $output);
            self::assertStringNotContainsString($email, $output);
        }

        $this->expectException(\LogicException::class);
        serialize($identity);
    }

    public function testRejectsMalformedProviderEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SocialIdentity::fromProvider(SocialProvider::Google, 'valid', 'not-an-email', true);
    }

    public function testEveryPurposeHasAFixedLocalReturnPath(): void
    {
        self::assertSame('/auth/complete', OAuthPurpose::Login->returnPath());
        self::assertSame('/settings/security', OAuthPurpose::Link->returnPath());
        self::assertSame('/settings/security', OAuthPurpose::Confirm->returnPath());
    }
}
