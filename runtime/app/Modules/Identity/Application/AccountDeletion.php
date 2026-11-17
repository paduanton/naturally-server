<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface AccountDeletion
{
    public function deleteForSession(int $actorId, string $sessionId): void;
}
