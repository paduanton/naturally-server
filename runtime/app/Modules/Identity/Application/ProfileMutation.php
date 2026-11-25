<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\DisplayName;
use App\Modules\Identity\Domain\Username;

interface ProfileMutation
{
    public function updateForSession(int $actorId, string $sessionId, ?DisplayName $name, ?Username $username, ?\App\Modules\Identity\Domain\BirthDate $birthday = null, bool $birthdayProvided = false): AccountData;
}
