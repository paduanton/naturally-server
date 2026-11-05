<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

final class VerifyBrowserRequest extends PreventRequestForgery
{
    // Security acceptance must exercise the same checks as production.
    protected function runningUnitTests(): bool { return false; }
}
