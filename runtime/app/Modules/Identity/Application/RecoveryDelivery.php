<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;

interface RecoveryDelivery
{
    public function requestForEmail(EmailAddress $email): void;
}
