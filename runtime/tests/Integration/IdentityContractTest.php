<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class IdentityContractTest extends TestCase
{
    public function createApplication(): Application
    {
        $app = RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    public function testDocumentedOperationsMatchEveryPublishedIdentityRoute(): void
    {
        $spec = json_decode(file_get_contents($this->app->resourcePath('contracts/identity.openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('3.1.0', $spec['openapi']);
        $actual = [];
        foreach ($this->app->make('router')->getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/v1/auth/')) {
                foreach ($route->methods() as $method) { if ($method !== 'HEAD') { $actual[] = strtolower($method).' /'.$route->uri(); } }
            }
        }
        $documented = [];
        $ids = [];
        foreach ($spec['paths'] as $uri => $operations) {
            foreach ($operations as $method => $operation) {
                $documented[] = $method.' '.$uri;
                $ids[] = $operation['operationId'];
                self::assertNotEmpty($operation['responses']);
                foreach ($operation['responses'] as $status => $response) {
                    if (in_array((int) $status, [202, 204], true)) { self::assertArrayNotHasKey('content', $response); }
                }
            }
        }
        sort($actual); sort($documented);
        self::assertSame($actual, $documented);
        self::assertCount(count(array_unique($ids)), $ids);
        self::assertSame(['sessionCookie', 'csrfToken'], array_keys($spec['components']['securitySchemes']));
        self::assertTrue($spec['components']['schemas']['Reset']['properties']['token']['writeOnly']);
        self::assertFalse($spec['components']['schemas']['Account']['properties']['data']['additionalProperties']);
    }
}
