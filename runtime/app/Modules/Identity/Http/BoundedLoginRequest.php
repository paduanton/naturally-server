<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Laravel\Fortify\Http\Requests\LoginRequest;

final class BoundedLoginRequest extends LoginRequest
{
    public function rules(): array
    {
        return ['email' => 'required|string|email:rfc|max:255', 'password' => 'required|string|max:1024', 'remember' => 'prohibited', 'remember_me' => 'prohibited'];
    }
}
