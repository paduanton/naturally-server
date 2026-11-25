<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;

final readonly class RequestAccountRestoration
{
    public function __construct(private RestorationDelivery $delivery) {}

    public function handle(RestorationRequestInput $input): void
    {
        $this->delivery->requestForEmail(EmailAddress::fromString($input->email));
    }
}
