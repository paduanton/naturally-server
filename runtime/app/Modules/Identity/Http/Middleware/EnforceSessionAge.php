<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

final class EnforceSessionAge
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = auth()->guard('web');
        $issuedAt = $request->session()->get('_identity_issued_at');
        $now = Date::now()->getTimestamp();
        if ($guard->check() && (!is_int($issuedAt) || $issuedAt > $now || $now - $issuedAt >= 43200)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new AuthenticationException();
        }
        return $next($request);
    }
}
