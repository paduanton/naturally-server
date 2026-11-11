<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\NewPassword;

interface PasswordMutation
{
    public function replaceForSession(int $actorId, string $sessionId, NewPassword $password): void;
}
