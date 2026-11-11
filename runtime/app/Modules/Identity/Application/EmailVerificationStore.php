<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface EmailVerificationStore
{
    public function verifyCurrentEmail(int $actorId, string $emailDigest): bool;
}
