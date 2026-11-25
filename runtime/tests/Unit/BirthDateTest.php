<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\BirthDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BirthDate::class)]
final class BirthDateTest extends TestCase
{
    #[DataProvider('invalidDates')]
    public function testRejectsNonIsoImpossibleAndDisallowedHistoricalDates(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BirthDate::fromString($value);
    }

    public static function invalidDates(): iterable
    {
        foreach (['', '2000/02/29', '2000-2-29', "2000-02-29\n", '2001-02-29', '2000-04-31', '0000-01-01', '1920-01-01', '1919-12-31'] as $value) { yield [$value]; }
    }

    #[DataProvider('ages')]
    public function testCalendarAgeBoundaryUsesWholeYears(string $birthday, string $today, bool $allowed): void
    {
        $date = BirthDate::fromString($birthday);
        if (!$allowed) { $this->expectException(\InvalidArgumentException::class); }
        $date->assertAllowedOn(new \DateTimeImmutable($today, new \DateTimeZone('UTC')));
        self::assertSame($birthday, $date->value);
    }

    public static function ages(): iterable
    {
        yield ['2019-10-14', '2026-10-14', true];
        yield ['2019-10-15', '2026-10-14', false];
        yield ['2026-10-15', '2026-10-14', false];
        yield ['2016-02-29', '2023-02-28', false];
        yield ['2016-02-29', '2023-03-01', true];
        yield ['1920-01-02', '2026-10-14', true];
    }
}
