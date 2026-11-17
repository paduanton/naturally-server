<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\NewPassword;
use App\Modules\Identity\Domain\RecoveryToken;

interface RestorationConsumer
{
    public function consume(EmailAddress $email, RecoveryToken $token, NewPassword $password): ?int;
}
