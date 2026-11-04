<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class AccountData
{
    public function __construct(public int $id, public string $name, public string $username, public string $email, public bool $verified) {}
}
