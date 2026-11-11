<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::prefix('api/v1/auth')->middleware('identity.browser')->group(static function (): void {
    Route::get('csrf-cookie', [CsrfCookieController::class, 'show'])->name('identity.csrf');
    Route::get('me', CurrentAccount::class)->middleware('auth:sanctum')->name('identity.me');
    Route::post('logout', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy'])->middleware('auth:web')->name('identity.logout');
    Route::delete('sessions/others', RevokeOtherSessions::class)->middleware(['auth:web', Middleware\RequireRecentAuthentication::class])->name('identity.sessions.others');
    Route::post('register', [\Laravel\Fortify\Http\Controllers\RegisteredUserController::class, 'store'])->middleware([\App\Modules\Identity\Http\Middleware\RequireGuest::class, 'identity.throttle:identity-registration'])->name('identity.register');
    Route::post('login', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])->middleware([\App\Modules\Identity\Http\Middleware\RequireGuest::class, 'identity.throttle:identity-login'])->name('identity.login');
});
