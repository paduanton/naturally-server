<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface RegistrationStore
{
    public function register(NewAccountData $account): AccountData;
}
