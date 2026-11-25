<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final class PhoneContactConflict extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Phone contact cannot be added.');
    }
}
