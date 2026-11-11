<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;

final class BoundedPasswordConfirmation extends ConfirmablePasswordController
{
    public function store(Request $request): Responsable
    {
        $request->validate(['password' => 'required|string|max:1024']);
        return parent::store($request);
    }
}
