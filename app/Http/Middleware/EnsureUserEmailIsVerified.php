<?php

namespace App\Http\Middleware;

use Closure;
use App\Users;

class EnsureUserEmailIsVerified
{

    public function handle($request, Closure $next)
    {
        if (!$request->user() || !($request->user() instanceof Users && $request->user()->hasVerifiedEmail())) {
            return abort(403, 'Your email address is not verified.');
        }

        return $next($request);
    }
}
