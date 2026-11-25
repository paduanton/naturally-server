<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;

final readonly class ChangeEmail
{
    public function __construct(private EmailMutation $emails, private VerificationDelivery $verification) {}

    public function handle(EmailChangeInput $input): bool
    {
        $changed = $this->emails->replaceForSession($input->actorId, $input->sessionId(), EmailAddress::fromString($input->email));
        if ($changed) { $this->verification->sendForActor($input->actorId); }
        return $changed;
    }
}
