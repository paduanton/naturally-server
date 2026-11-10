<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;

final class StampAuthentication
{
    public function handle(Login $event): void
    {
        $request = request();
        if ($event->guard === 'web' && $request->hasSession()) {
            $request->session()->put('_identity_issued_at', Date::now()->getTimestamp());
            $request->session()->put('auth.password_confirmed_at', Date::now()->getTimestamp());
        }
    }
}
