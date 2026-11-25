<?php

declare(strict_types=1);

use App\Shared\Infrastructure\RuntimeConfiguration;

$settings = app(RuntimeConfiguration::class);

return [
    'default' => $settings->environment === 'testing' ? 'array' : 'smtp',
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => $settings->mail['host'],
            'port' => $settings->mail['port'],
            'timeout' => 10,
            'require_tls' => $settings->environment === 'production',
        ],
        'array' => ['transport' => 'array'],
    ],
    // Local sender only; production provider and verified sender are still pending.
    'from' => ['address' => 'noreply@naturally.test', 'name' => 'Naturally'],
];
