<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Testing\TestResponse;

abstract class BrowserTestCase extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';
    protected array $browserCookies = [];
    protected ?string $browserIp = null;

    protected function browser(string $method, string $path, array $data = [], array $server = [], bool $csrf = true): TestResponse
    {
        $this->app->make('auth')->forgetGuards();
        $this->app->forgetInstance('auth.driver');
        $this->app->make('session')->forgetDrivers();
        $this->app->forgetInstance('session.store');
        foreach ($this->app->make('router')->getRoutes() as $route) { $route->flushController(); }
        $this->browserIp ??= '2001:db8:'.implode(':', str_split(bin2hex(random_bytes(10)), 4)).':1';
        $headers = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'HTTP_ORIGIN' => 'https://naturally.test', 'REMOTE_ADDR' => $this->browserIp];
        if ($csrf && isset($this->browserCookies['XSRF-TOKEN'])) {
            $headers['HTTP_X_XSRF_TOKEN'] = urldecode($this->browserCookies['XSRF-TOKEN']);
        }
        $url = 'https://'.($server['HTTP_HOST'] ?? 'naturally.test').$path;
        $response = $this->call($method, $url, [], $this->browserCookies, [], array_replace($headers, $server), json_encode($data, JSON_THROW_ON_ERROR));
        foreach ($response->headers->getCookies() as $cookie) {
            $this->browserCookies[$cookie->getName()] = $cookie->getValue();
        }
        return $response;
    }

    protected function bootstrapBrowser(): void
    {
        $this->browser('GET', '/api/v1/auth/csrf-cookie')->assertNoContent();
    }
}
