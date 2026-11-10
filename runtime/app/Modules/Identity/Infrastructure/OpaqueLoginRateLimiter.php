<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter;

final class OpaqueLoginRateLimiter extends LoginRateLimiter
{
    protected function throttleKey(Request $request): string
    {
        $email = $request->input('email');
        $identity = is_string($email) ? strtolower(trim($email)) : '';
        return hash_hmac('sha256', $identity.'|'.$request->ip(), config('app.key'));
    }
}
