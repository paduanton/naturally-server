<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class RequireRecentAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = $request->session()->get('auth.password_confirmed_at');
        $now = Date::now()->getTimestamp();
        if (!is_int($confirmedAt) || $confirmedAt > $now || $now - $confirmedAt >= config('auth.password_timeout')) {
            throw new HttpException(423);
        }
        return $next($request);
    }
}
