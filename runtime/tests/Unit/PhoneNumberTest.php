<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\PhoneNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhoneNumber::class)]
final class PhoneNumberTest extends TestCase
{
    public function testCanonicalRepresentationIsPreservedWithoutGuessingCountryOrFormatting(): void
    {
        foreach (['+19990000001', '+123456789012345', '+12'] as $input) {
            $number = PhoneNumber::fromString($input);
            self::assertSame($input, $number->value);
            self::assertSame($input, (string) $number);
        }
    }

    #[DataProvider('invalidNumbers')]
    public function testRejectsNonCanonicalInputWithoutIncludingTheValueInTheError(string $input): void
    {
        try {
            PhoneNumber::fromString($input);
            self::fail('Non-canonical phone number was accepted.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Phone number must use canonical international notation.', $exception->getMessage());
        }
    }

    public static function invalidNumbers(): iterable
    {
        foreach (['', '19990000001', '+09990000001', '+1', '+1234567890123456', '+1 999 0000001', ' +19990000001', '+19990000001 ', '+19990000001x', "\n+19990000001", "+19990000001\n", '+１２３４５６７８９０'] as $index => $input) {
            yield 'invalid notation '.$index => [$input];
        }
    }
}
