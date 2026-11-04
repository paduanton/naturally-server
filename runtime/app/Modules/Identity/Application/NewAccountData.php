<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\Username;
use App\Modules\Identity\Domain\NewPassword;

final readonly class NewAccountData
{
    public function __construct(public string $name, public Username $username, public EmailAddress $email, public NewPassword $password) {}
}
