<?php

declare(strict_types=1);

use App\Shared\Infrastructure\RuntimeConfiguration;

$settings = app(RuntimeConfiguration::class);

return [
    'default' => 'redis',
    'stores' => [
        'array' => ['driver' => 'array', 'serialize' => false],
        'redis' => ['driver' => 'redis', 'connection' => 'cache', 'lock_connection' => 'default'],
    ],
    'prefix' => 'naturally:'.$settings->environment.':cache:',
];
