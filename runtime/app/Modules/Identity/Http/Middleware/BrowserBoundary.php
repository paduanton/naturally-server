<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BrowserBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $request->attributes->set('correlation_id', bin2hex(random_bytes(16)));
        $url = parse_url(config('app.url'));
        $origin = $url['scheme'].'://'.$url['host'].(isset($url['port']) ? ':'.$url['port'] : '');
        abort_unless($request->getSchemeAndHttpHost() === $origin, 403);
        $sentOrigin = $request->headers->get('Origin');
        abort_if(($sentOrigin !== null && $sentOrigin !== $origin) || $request->header('Sec-Fetch-Site') === 'cross-site', 403);
        abort_if(!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) && !$request->isJson(), 415);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Request-ID', $request->attributes->get('correlation_id'));
        return $response;
    }
}
