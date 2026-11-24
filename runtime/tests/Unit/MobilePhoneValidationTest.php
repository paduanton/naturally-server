<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\PhoneNumber;
use App\Modules\Identity\Infrastructure\LibphonenumberMobileValidation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LibphonenumberMobileValidation::class)]
#[UsesClass(PhoneNumber::class)]
final class MobilePhoneValidationTest extends TestCase
{
    #[DataProvider('numbers')]
    public function testRegionalNumberingMetadataDeterminesMobileCompatibility(string $countryCode, string $number, bool $accepted): void
    {
        self::assertSame($accepted, (new LibphonenumberMobileValidation())->accepts(PhoneNumber::fromString($number), $countryCode));
    }

    public static function numbers(): iterable
    {
        yield 'Brazilian mobile' => ['BR', '+5511999990001', true];
        yield 'British mobile' => ['GB', '+447400123456', true];
        yield 'US fixed/mobile indistinguishable' => ['US', '+12025550123', true];
        yield 'Canadian fixed/mobile indistinguishable' => ['CA', '+14165550123', true];
        yield 'Brazilian fixed line' => ['BR', '+551132345678', false];
        yield 'British fixed line' => ['GB', '+442079460123', false];
        yield 'US toll free' => ['US', '+18005550123', false];
        yield 'Foreign country' => ['GB', '+5511999990001', false];
        yield 'Foreign calling code' => ['BR', '+12025550123', false];
        yield 'Shared calling code foreign region' => ['US', '+14165550123', false];
        yield 'Unknown region' => ['ZZ', '+5511999990001', false];
        yield 'Impossible national number' => ['BR', '+5500999990001', false];
        yield 'Too short national number' => ['BR', '+5511', false];
        yield 'Unknown calling code' => ['BR', '+999123456789', false];
        yield 'Too long national number' => ['BR', '+551199999000111', false];
    }
}
