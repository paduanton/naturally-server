<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Symfony\Component\HttpFoundation\Response;

final class SessionResponse implements \Laravel\Fortify\Contracts\LogoutResponse, \Laravel\Fortify\Contracts\PasswordConfirmedResponse
{
    public function toResponse($request): Response { return new Response('', 204, ['Cache-Control' => 'no-store, private']); }
}
