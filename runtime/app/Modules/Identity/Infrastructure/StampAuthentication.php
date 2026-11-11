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
            $request->session()->put('password_hash_web', auth()->guard('web')->hashPasswordForCookie($event->user->getAuthPassword()));
            // A login event can also come from a social provider. Only successful
            // local credential responses establish password confirmation.
            $request->session()->forget('auth.password_confirmed_at');
        }
    }
}
