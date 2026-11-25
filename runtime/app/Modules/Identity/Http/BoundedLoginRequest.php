<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Laravel\Fortify\Http\Requests\LoginRequest;

final class BoundedLoginRequest extends LoginRequest
{
    public function rules(): array
    {
        return ['email' => 'required_without:login|string|email:rfc|max:255|prohibits:login', 'login' => 'required_without:email|string|max:255|prohibits:email', 'password' => 'required|string|max:1024', 'remember' => 'prohibited', 'remember_me' => 'prohibited'];
    }

    protected function passedValidation(): void
    {
        if ($this->has('login')) { $this->merge(['email' => $this->input('login')]); }
    }
}
