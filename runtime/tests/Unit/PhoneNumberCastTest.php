<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\PhoneNumber;
use App\Modules\Identity\Infrastructure\Database\Casts\PhoneNumberCast;
use App\Modules\Identity\Infrastructure\Persistence\Phone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhoneNumberCast::class)]
#[UsesClass(PhoneNumber::class)]
#[UsesClass(Phone::class)]
final class PhoneNumberCastTest extends TestCase
{
    public function testRoundTripsStringsAndDomainValuesWithoutCoercion(): void
    {
        $cast = new PhoneNumberCast();
        $model = new Phone();
        $number = PhoneNumber::fromString('+19990000001');
        self::assertSame($number->value, $cast->set($model, 'number', $number, []));
        self::assertSame($number->value, $cast->set($model, 'number', $number->value, []));
        self::assertSame($number->value, $cast->get($model, 'number', $number->value, [])->value);
    }

    #[DataProvider('unsupportedValues')]
    public function testRejectsUnsupportedWriteTypesWithoutStringConversionWarnings(mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new PhoneNumberCast())->set(new Phone(), 'number', $value, []);
    }

    #[DataProvider('unsupportedValues')]
    public function testRejectsCorruptedStorageTypesWithoutStringConversionWarnings(mixed $value): void
    {
        $this->expectException(\UnexpectedValueException::class);
        (new PhoneNumberCast())->get(new Phone(), 'number', $value, []);
    }

    public static function unsupportedValues(): iterable
    {
        yield 'null' => [null];
        yield 'number' => [19990000001];
        yield 'array' => [[]];
        yield 'object' => [new \stdClass()];
        yield 'other stringable' => [new class implements \Stringable {
            public function __toString(): string
            {
                return '+19990000001';
            }
        }];
    }
}
