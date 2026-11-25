<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversClass(\App\Modules\Identity\Http\Middleware\BrowserBoundary::class)]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserOriginTest extends BrowserTestCase
{
    public function testCanonicalBrowserOriginMatchesConfiguredDefaultPortAndHostCase(): void
    {
        $this->app->make('config')->set('app.url', 'https://NATURALLY.TEST:443');
        $this->browser('GET', '/api/v1/auth/csrf-cookie')->assertNoContent();
        $this->browser('GET', '/api/v1/auth/csrf-cookie', server: ['HTTP_ORIGIN' => 'https://naturally.test:8443'])->assertForbidden();
    }

    public function testNonDefaultPortMustMatchExactly(): void
    {
        $this->app->make('config')->set('app.url', 'https://naturally.test:8443');
        $this->browser('GET', '/api/v1/auth/csrf-cookie', server: ['HTTP_HOST' => 'naturally.test:8443', 'HTTP_ORIGIN' => 'https://naturally.test:8443'])->assertNoContent();
        $this->browser('GET', '/api/v1/auth/csrf-cookie')->assertForbidden();
    }
}
