<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__).'/CoverageReport.php';

#[CoversClass(CoverageReport::class)]
#[CoversFile(__DIR__.'/../check.php')]
final class CoverageReportTest extends TestCase
{
    #[DataProvider('thresholdCases')]
    public function testUsesActualCountsForBothThresholds(int $lines, int $branches, bool $passed): void
    {
        $result = CoverageReport::evaluate($this->report($lines, $branches));
        self::assertSame($passed, $result['passed']);
        self::assertSame($lines, $result['lines']['covered']);
        self::assertSame($branches, $result['observedBranches']['covered']);
    }

    public static function thresholdCases(): iterable
    {
        yield 'both at threshold' => [90, 90, true];
        yield 'line below' => [89, 100, false];
        yield 'branch below' => [100, 89, false];
        yield 'both below' => [20, 40, false];
        yield 'both above' => [100, 100, true];
    }

    public function testUnknownBranchFilesAreVisibleAndCannotPassStrictAcceptance(): void
    {
        $report = $this->report(95, 95, 2);
        $normal = CoverageReport::evaluate($report);
        self::assertTrue($normal['passed']);
        self::assertSame(2, $normal['filesWithoutBranchData']);
        self::assertFalse($normal['branchCollectionComplete']);
        self::assertFalse($normal['certifiesGlobalRelease']);
        self::assertFalse(CoverageReport::evaluate($report, requireCompleteBranches: true)['passed']);
    }

    #[DataProvider('invalidReports')]
    public function testRejectsMissingAmbiguousAndImpossibleReports(string $report): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Coverage summary is missing or invalid.');
        CoverageReport::evaluate($report);
    }

    public static function invalidReports(): iterable
    {
        yield 'empty' => [''];
        yield 'no summary' => ['Lines: 100.00% (100/100)'];
        yield 'no branches' => ["Summary:\n Lines: 100.00% (100/100)\n\n"];
        yield 'no lines' => ["Summary:\n Branches: 100.00% (100/100)\n\n"];
        yield 'zero source' => ["Summary:\n Branches: 0.00% (0/0)\n Lines: 0.00% (0/0)\n\n"];
        yield 'impossible lines' => ["Summary:\n Branches: 100.00% (100/100)\n Lines: 100.00% (101/100)\n\n"];
        yield 'impossible branches' => ["Summary:\n Branches: 100.00% (101/100)\n Lines: 100.00% (100/100)\n\n"];
        yield 'two summaries' => ["Summary:\nSummary:\n Lines: 100.00% (100/100)\n Branches: 100.00% (100/100)\n\n"];
        yield 'overflow' => ["Summary:\n Branches: 100.00% (100/100)\n Lines: 0.00% (1/9999999999999999999999999999)\n\n"];
    }

    public function testHandlesCrLfAndDoesNotTrustDisplayedPercentagesOrClassSections(): void
    {
        $text = str_replace("\n", "\r\n", $this->report(89, 91));
        $text .= "Example\r\n Lines: 100.00% (100/100) Branches: 100.00% (100/100)\r\n";
        self::assertFalse(CoverageReport::evaluate($text)['passed']);
    }

    public function testCliReturnsDistinctResultsWithoutExposingMalformedInput(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'coverage-');
        try {
            file_put_contents($path, $this->report(95, 95, 2));
            $normal = new Process([PHP_BINARY, __DIR__.'/../check.php', $path]);
            self::assertSame(0, $normal->run());
            $result = json_decode($normal->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            self::assertFalse($result['branchCollectionComplete']);
            $strict = new Process([PHP_BINARY, __DIR__.'/../check.php', $path, '--require-complete-branches']);
            self::assertSame(1, $strict->run());
            file_put_contents($path, bin2hex(random_bytes(32)));
            $invalid = new Process([PHP_BINARY, __DIR__.'/../check.php', $path]);
            self::assertSame(2, $invalid->run());
            self::assertSame('', $invalid->getOutput());
            self::assertSame("Coverage report could not be verified.\n", $invalid->getErrorOutput());
            $missing = new Process([PHP_BINARY, __DIR__.'/../check.php']);
            self::assertSame(2, $missing->run());
            $badFlag = new Process([PHP_BINARY, __DIR__.'/../check.php', $path, '--unknown']);
            self::assertSame(2, $badFlag->run());
        } finally {
            unlink($path);
        }
    }

    private function report(int $lines, int $branches, int $unknownFiles = 0): string
    {
        $missing = $unknownFiles === 0 ? '' : " * $unknownFiles files were not loaded during test execution and no branch/path data is available for them\n";
        return "Code Coverage Report:\n\n Summary:\n Classes: 100.00% (1/1)\n Methods: 100.00% (1/1)\n Branches: 100.00% ($branches/100)\n$missing Lines: 100.00% ($lines/100)\n\n";
    }
}
