<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\AccountData;
use App\Modules\Identity\Application\AccountReader;

final class EloquentAccountReader implements AccountReader
{
    public function forActor(int $actorId): ?AccountData
    {
        $user = User::find($actorId);
        return $user === null ? null : new AccountData($user->id, $user->name, $user->username, $user->email, $user->email_verified_at !== null);
    }
}
