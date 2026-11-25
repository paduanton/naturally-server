<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse;

final class RegisteredResponse implements RegisterResponse
{
    public function toResponse($request): JsonResponse
    {
        $request->session()->put('auth.password_confirmed_at', \Illuminate\Support\Facades\Date::now()->getTimestamp());
        return new JsonResponse(['data' => ['id' => (int) $request->user()->getAuthIdentifier()]], 201, ['Cache-Control' => 'no-store, private']);
    }
}
