<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\User;

return [
    'defaults' => ['guard' => 'web', 'passwords' => 'users'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'users']],
    'providers' => ['users' => ['driver' => 'eloquent', 'model' => User::class]],
    'passwords' => [
        'users' => [
            'provider' => 'users', 'table' => 'password_reset_tokens',
            'expire' => 30, 'throttle' => 60,
        ],
    ],
    'password_timeout' => 900,
];
