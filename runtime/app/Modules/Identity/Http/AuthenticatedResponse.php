<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Support\Facades\Date;
use Laravel\Fortify\Contracts\LoginResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticatedResponse implements LoginResponse
{
    public function toResponse($request): Response
    {
        $request->session()->put('auth.password_confirmed_at', Date::now()->getTimestamp());
        return new Response('', 204, ['Cache-Control' => 'no-store, private']);
    }
}
