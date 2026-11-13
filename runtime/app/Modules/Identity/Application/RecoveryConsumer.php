<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\RecoveryToken;
use App\Modules\Identity\Domain\NewPassword;

interface RecoveryConsumer
{
    public function consume(EmailAddress $email, #[\SensitiveParameter] RecoveryToken $token, #[\SensitiveParameter] NewPassword $password): bool;
}
