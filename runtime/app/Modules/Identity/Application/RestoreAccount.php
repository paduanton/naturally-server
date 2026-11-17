<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\NewPassword;
use App\Modules\Identity\Domain\RecoveryToken;

final readonly class RestoreAccount
{
    public function __construct(private RestorationConsumer $restoration, private VerificationDelivery $verification) {}

    public function handle(AccountRestoreInput $input): void
    {
        $actorId = $this->restoration->consume(
            EmailAddress::fromString($input->email),
            RecoveryToken::fromString($input->token()),
            NewPassword::fromString($input->password()),
        );
        if ($actorId === null) {
            throw new RestorationDenied();
        }

        $this->verification->sendForActor($actorId);
    }
}
