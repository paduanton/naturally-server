<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface SessionRevocation
{
    public function revokeOthers(int $actorId, string $currentSessionId): void;
}
