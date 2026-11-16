<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\DisplayName;
use App\Modules\Identity\Domain\Username;

final readonly class ChangeProfile
{
    public function __construct(private ProfileMutation $profiles) {}

    public function handle(ProfileChangeInput $input): AccountData
    {
        if ($input->name === null && $input->username === null) { throw new \InvalidArgumentException('No profile changes supplied.'); }
        return $this->profiles->updateForSession($input->actorId, $input->sessionId(), $input->name === null ? null : DisplayName::fromString($input->name), $input->username === null ? null : Username::fromString($input->username));
    }
}
