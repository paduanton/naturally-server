<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Identity\Domain\DisplayName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisplayName::class)]
final class DisplayNameTest extends TestCase
{
    public function testNamesNormalizeWhitespaceAndRetainUnicode(): void
    {
        self::assertSame('Nome Sintético', DisplayName::fromString(' Nome Sintético ')->value);
        self::assertSame(255, mb_strlen(DisplayName::fromString(str_repeat('🙂', 255))->value));
    }

    public function testBlankOversizedMalformedAndControlBearingNamesAreRejected(): void
    {
        foreach ([' ', str_repeat('a', 256), "Name\x00suffix", "Name\nsuffix", "\xff"] as $name) {
            try { DisplayName::fromString($name); self::fail('Invalid display name accepted.'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
    }
}
