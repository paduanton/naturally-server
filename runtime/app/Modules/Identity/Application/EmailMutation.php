<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;

interface EmailMutation
{
    public function replaceForSession(int $actorId, string $sessionId, EmailAddress $email): bool;
}
