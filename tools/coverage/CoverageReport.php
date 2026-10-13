<?php

declare(strict_types=1);

final class CoverageReport
{
    public static function evaluate(string $report, bool $requireCompleteBranches = false): array
    {
        if (preg_match_all('/^\s*Summary:\s*$/m', $report) !== 1
            || !preg_match('/Summary:\s*\R(.*?)(?:\R\R|\z)/s', $report, $summary)) {
            throw new RuntimeException('Coverage summary is missing or invalid.');
        }
        $lines = self::metric($summary[1], 'Lines');
        $branches = self::metric($summary[1], 'Branches');
        $missing = preg_match('/\*\s+(\d+) files were not loaded.*no branch\/path data/', $summary[1], $match) ? self::integer($match[1]) : 0;
        $complete = $missing === 0;
        return [
            'scope' => 'modern-application',
            'minimumPercent' => 90,
            'lines' => $lines,
            // Only branches actually available in Xdebug's report; never infer missing ones.
            'observedBranches' => $branches,
            'filesWithoutBranchData' => $missing,
            'branchCollectionComplete' => $complete,
            'certifiesGlobalRelease' => false,
            'passed' => $lines['percent'] >= 90 && $branches['percent'] >= 90 && (!$requireCompleteBranches || $complete),
        ];
    }

    private static function metric(string $summary, string $name): array
    {
        if (!preg_match('/^\s*'.$name.':\s*\d+(?:\.\d+)?%\s*\((\d+)\/(\d+)\)\s*$/m', $summary, $match)) {
            throw new RuntimeException('Coverage summary is missing or invalid.');
        }
        $covered = self::integer($match[1]);
        $total = self::integer($match[2]);
        if ($total === 0 || $covered > $total) {
            throw new RuntimeException('Coverage summary is missing or invalid.');
        }
        return ['covered' => $covered, 'total' => $total, 'percent' => 100 * $covered / $total];
    }

    private static function integer(string $value): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || $integer < 0) {
            throw new RuntimeException('Coverage summary is missing or invalid.');
        }
        return $integer;
    }
}
