<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Laravel\Fortify\Contracts\PasswordUpdateResponse;
use Symfony\Component\HttpFoundation\Response;

final class PasswordChangedResponse implements PasswordUpdateResponse
{
    public function toResponse($request): Response
    {
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return new Response('', 204, ['Cache-Control' => 'no-store, private']);
    }
}
