<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Domain\LoginIdentifier;
use App\Modules\Identity\Infrastructure\Persistence\User;

final class LoginThrottleIdentity
{
    public function forIdentifier(mixed $input): string
    {
        if (!is_string($input)) { return 'invalid'; }
        try { $identifier = LoginIdentifier::fromString($input); }
        catch (\InvalidArgumentException) { return 'invalid'; }
        $id = User::query()->where($identifier->column, $identifier->value)->value('id');
        return $id === null ? 'unknown:'.$identifier->value : 'account:'.$id;
    }
}
