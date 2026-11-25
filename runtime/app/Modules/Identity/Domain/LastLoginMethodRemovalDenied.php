<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final class LastLoginMethodRemovalDenied extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The last login method cannot be removed.');
    }
}
