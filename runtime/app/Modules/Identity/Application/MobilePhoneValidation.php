<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\PhoneNumber;

interface MobilePhoneValidation
{
    /** Country code must already be normalized; this does not verify ownership or reachability. */
    public function accepts(PhoneNumber $number, string $countryCode): bool;
}
