<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter;

final class OpaqueLoginRateLimiter extends LoginRateLimiter
{
    protected function throttleKey(Request $request): string
    {
        $identity = app(LoginThrottleIdentity::class)->forIdentifier($request->input('login', $request->input('email')));
        return hash_hmac('sha256', $identity.'|'.$request->ip(), config('app.key'));
    }
}
