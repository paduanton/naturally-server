<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;

final readonly class RequestPasswordRecovery
{
    public function __construct(private RecoveryDelivery $delivery) {}

    public function handle(RecoveryRequestInput $input): void
    {
        $this->delivery->requestForEmail(EmailAddress::fromString($input->email));
    }
}
