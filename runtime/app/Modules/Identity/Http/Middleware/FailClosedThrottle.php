<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class FailClosedThrottle extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        try { return parent::handle(...func_get_args()); }
        catch (\RedisException) { throw new HttpException(503, 'Authentication limiter unavailable.'); }
    }
}
