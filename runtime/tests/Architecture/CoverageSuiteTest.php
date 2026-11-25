<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class CoverageSuiteTest extends TestCase
{
    public function testJointSuiteIncludesEveryRegisteredTestExactlyOnce(): void
    {
        $expected = array_merge($this->files('phpunit.xml'), $this->files('phpunit.services.xml'));
        sort($expected);
        $actual = $this->files('phpunit.foundation.xml');
        sort($actual);
        self::assertSame(array_values(array_unique($expected)), $actual);
        self::assertCount(count(array_unique($actual)), $actual);
    }

    public function testCoverageRetainsAllSourceDirectoriesAndDisallowsSuppressions(): void
    {
        foreach (['phpunit.xml', 'phpunit.services.xml', 'phpunit.foundation.xml'] as $configuration) {
            $xml = $this->configuration($configuration);
            $directories = array_map(static fn ($node): string => (string) $node, $xml->xpath('//source/include/directory'));
            sort($directories);
            self::assertSame(['app', 'bootstrap', 'config', 'public', 'routes'], $directories);
            self::assertSame('artisan', (string) $xml->source->include->file);
            self::assertCount(0, $xml->xpath('//source/exclude'));
            self::assertSame('true', (string) $xml->coverage['includeUncoveredFiles']);
            self::assertSame('true', (string) $xml->coverage['branchCoverage']);
            self::assertSame('true', (string) $xml->coverage['disableCodeCoverageIgnore']);
            self::assertSame('true', (string) $xml['failOnAllIssues']);
            self::assertSame('true', (string) $xml['failOnEmptyTestSuite']);
        }
    }

    private function files(string $name): array
    {
        return array_map(static fn ($node): string => (string) $node, $this->configuration($name)->xpath('//testsuite/file'));
    }

    private function configuration(string $name): \SimpleXMLElement
    {
        $path = dirname(__DIR__, 2).'/'.$name;
        self::assertFileExists($path);
        $xml = simplexml_load_file($path, options: LIBXML_NONET);
        self::assertInstanceOf(\SimpleXMLElement::class, $xml);
        return $xml;
    }
}
