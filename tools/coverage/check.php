<?php

declare(strict_types=1);

require __DIR__.'/CoverageReport.php';

try {
    if (($argc !== 2 && $argc !== 3) || ($argc === 3 && $argv[2] !== '--require-complete-branches')) {
        throw new RuntimeException('Invalid coverage arguments.');
    }
    $text = @file_get_contents($argv[1]);
    if ($text === false) {
        throw new RuntimeException('Coverage report is unavailable.');
    }
    $result = CoverageReport::evaluate($text, requireCompleteBranches: $argc === 3);
    fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n");
    exit($result['passed'] ? 0 : 1);
} catch (Throwable) {
    fwrite(STDERR, "Coverage report could not be verified.\n");
    exit(2);
}
