<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\Username;
use App\Modules\Identity\Domain\NewPassword;

final readonly class RegisterAccount
{
    public function __construct(private RegistrationStore $store) {}

    public function handle(RegistrationInput $input): AccountData
    {
        $name = trim($input->name);
        if (!mb_check_encoding($name, 'UTF-8') || mb_strlen($name) < 1 || mb_strlen($name) > 255 || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            throw new \InvalidArgumentException('Invalid display name.');
        }
        return $this->store->register(new NewAccountData($name, Username::fromString($input->username), EmailAddress::fromString($input->email), NewPassword::fromString($input->password())));
    }
}
