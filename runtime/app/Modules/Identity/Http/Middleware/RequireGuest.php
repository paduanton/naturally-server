<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user('web') !== null, 409);
        return $next($request);
    }
}
