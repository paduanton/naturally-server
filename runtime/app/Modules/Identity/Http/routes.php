<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::prefix('api/v1/auth')->middleware('identity.browser')->group(static function (): void {
    Route::get('csrf-cookie', [CsrfCookieController::class, 'show'])->name('identity.csrf');
    Route::post('register', [\Laravel\Fortify\Http\Controllers\RegisteredUserController::class, 'store'])->middleware([\App\Modules\Identity\Http\Middleware\RequireGuest::class, 'throttle:identity-registration'])->name('identity.register');
});
