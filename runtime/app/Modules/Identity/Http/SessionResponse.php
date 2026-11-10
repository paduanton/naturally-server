<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Laravel\Fortify\Contracts\LoginResponse;
use Symfony\Component\HttpFoundation\Response;

final class SessionResponse implements LoginResponse
{
    public function toResponse($request): Response { return new Response('', 204, ['Cache-Control' => 'no-store, private']); }
}
