<?php

declare(strict_types=1);

use App\Shared\Infrastructure\RuntimeConfiguration;

$settings = app(RuntimeConfiguration::class);

return [
    'name' => 'Naturally',
    'env' => $settings->environment,
    'debug' => $settings->debug,
    'url' => $settings->url,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => $settings->applicationKey(),
    'cipher' => 'AES-256-CBC',
];
