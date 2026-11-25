<?php

declare(strict_types=1);

use App\Shared\Infrastructure\RuntimeConfiguration;

$settings = app(RuntimeConfiguration::class);

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $settings->database['host'],
            'port' => $settings->database['port'],
            'database' => $settings->database['name'],
            'username' => $settings->database['user'],
            'password' => $settings->databasePassword(),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'mask_bindings_in_exception_messages' => true,
            'engine' => null,
        ],
    ],
    'migrations' => ['table' => 'migrations', 'update_date_on_publish' => true],
    'redis' => [
        'client' => 'phpredis',
        'options' => ['prefix' => 'naturally:'.$settings->environment.':'],
        'default' => [
            'host' => $settings->redis['host'],
            'port' => $settings->redis['port'],
            'database' => 0,
        ],
        'cache' => [
            'host' => $settings->redis['host'],
            'port' => $settings->redis['port'],
            'database' => 1,
        ],
    ],
];
