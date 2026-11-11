<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\NewPassword;

final readonly class ChangePassword
{
    public function __construct(private PasswordMutation $passwords) {}

    public function handle(PasswordChangeInput $input): void
    {
        $this->passwords->replaceForSession($input->actorId, $input->sessionId(), NewPassword::fromString($input->password()));
    }
}
