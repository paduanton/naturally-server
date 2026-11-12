<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[CoversDirectoryRecursively(__DIR__.'/../../app/Shared/Http')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserTransportTest extends BrowserTestCase
{
    public function testCsrfBootstrapUsesSecureDistinctCookiePrivileges(): void
    {
        $response = $this->browser('GET', '/api/v1/auth/csrf-cookie')->assertNoContent();
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) { $cookies[$cookie->getName()] = $cookie; }
        self::assertTrue($cookies['naturally_session']->isHttpOnly());
        self::assertTrue($cookies['naturally_session']->isSecure());
        self::assertSame('lax', $cookies['naturally_session']->getSameSite());
        self::assertNull($cookies['naturally_session']->getDomain());
        self::assertFalse($cookies['XSRF-TOKEN']->isHttpOnly());
    }

    public function testRealCsrfProtectionRunsInTestingAndAcceptsOnlyMatchingToken(): void
    {
        Route::post('/api/v1/auth/probe', static fn () => response()->json(['ok' => true]))->middleware('identity.browser');
        $this->browser('POST', '/api/v1/auth/probe', csrf: false)->assertStatus(419)->assertJsonPath('code', 'csrf_mismatch');
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/probe')->assertOk();
        $this->browser('POST', '/api/v1/auth/probe', server: ['HTTP_X_XSRF_TOKEN' => bin2hex(random_bytes(24))])->assertStatus(419);
    }

    public function testForeignOriginsHostsAndUnsupportedContentTypesAreRejected(): void
    {
        Route::post('/api/v1/auth/probe', static fn () => response()->json(['ok' => true]))->middleware('identity.browser');
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/probe', server: ['HTTP_ORIGIN' => 'https://hostile.test'])->assertForbidden();
        $this->browser('POST', '/api/v1/auth/probe', server: ['HTTP_HOST' => 'hostile.test'])->assertForbidden();
        $this->browser('POST', '/api/v1/auth/probe', server: ['CONTENT_TYPE' => 'text/plain'])->assertStatus(415);
    }

    public function testApiAuthenticationIsSessionOnlyAndDefaultPackageRoutesStayDisabled(): void
    {
        Route::get('/api/v1/auth/private-probe', static fn () => response()->json(['ok' => true]))->middleware(['identity.browser', 'auth:sanctum']);
        $response = $this->browser('GET', '/api/v1/auth/private-probe', server: ['HTTP_AUTHORIZATION' => 'Bearer '.bin2hex(random_bytes(24))]);
        $response->assertUnauthorized()->assertJsonPath('code', 'unauthenticated')->assertHeader('Content-Type', 'application/problem+json');
        self::assertMatchesRegularExpression('/\A[a-f0-9]{32}\z/', $response->json('correlation_id'));
        foreach (['/login', '/register', '/sanctum/csrf-cookie'] as $path) { $this->browser('GET', $path)->assertNotFound(); }
    }

    public function testPublishedBrowserRoutesMatchTheExplicitContract(): void
    {
        $routes = [];
        foreach ($this->app->make('router')->getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/v1/auth/')) { $routes[$route->uri()] = $route->methods(); }
        }
        ksort($routes);
        self::assertSame([
            'api/v1/auth/csrf-cookie' => ['GET', 'HEAD'],
            'api/v1/auth/email/verification-notification' => ['POST'],
            'api/v1/auth/email/verify/{id}/{hash}' => ['GET', 'HEAD'],
            'api/v1/auth/login' => ['POST'],
            'api/v1/auth/logout' => ['POST'],
            'api/v1/auth/me' => ['GET', 'HEAD'],
            'api/v1/auth/password' => ['PUT'],
            'api/v1/auth/password/confirm' => ['POST'],
            'api/v1/auth/register' => ['POST'],
            'api/v1/auth/sessions/others' => ['DELETE'],
        ], $routes);
    }
}
