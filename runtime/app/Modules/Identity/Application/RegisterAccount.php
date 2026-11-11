<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\Username;
use App\Modules\Identity\Domain\NewPassword;
use App\Modules\Identity\Domain\DisplayName;

final readonly class RegisterAccount
{
    public function __construct(private RegistrationStore $store) {}

    public function handle(RegistrationInput $input): AccountData
    {
        return $this->store->register(new NewAccountData(DisplayName::fromString($input->name), Username::fromString($input->username), EmailAddress::fromString($input->email), NewPassword::fromString($input->password())));
    }
}
