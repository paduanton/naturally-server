<?php

declare(strict_types=1);

use App\Shared\Infrastructure\RuntimeConfiguration;

$settings = app(RuntimeConfiguration::class);

return [
    'driver' => 'database',
    'connection' => 'mysql',
    'table' => 'sessions',
    // Idle timeout only; Identity must also enforce absolute session expiry.
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => true,
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => 'naturally_session',
    'path' => '/',
    'domain' => null,
    'secure' => strtolower(parse_url($settings->url, PHP_URL_SCHEME)) === 'https',
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
    'files' => storage_path('framework/sessions'),
];
