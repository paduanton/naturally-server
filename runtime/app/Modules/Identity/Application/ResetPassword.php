<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\RecoveryToken;
use App\Modules\Identity\Domain\NewPassword;

final readonly class ResetPassword
{
    public function __construct(private RecoveryConsumer $consumer) {}

    public function handle(PasswordResetInput $input): void
    {
        if (!$this->consumer->consume(EmailAddress::fromString($input->email), RecoveryToken::fromString($input->token()), NewPassword::fromString($input->password()))) {
            throw new RecoveryDenied();
        }
    }
}
