<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class VerifyEmail
{
    public function __construct(private EmailVerificationStore $store) {}

    public function handle(EmailVerificationInput $input): void
    {
        if ($input->actorId !== $input->targetId || !$this->store->verifyCurrentEmail($input->actorId, $input->emailDigest)) {
            throw new EmailVerificationDenied();
        }
    }
}
