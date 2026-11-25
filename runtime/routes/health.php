<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health/live', static fn (): JsonResponse => new JsonResponse(
    ['status' => 'up'], headers: ['Cache-Control' => 'no-store'],
))->name('health.live');
