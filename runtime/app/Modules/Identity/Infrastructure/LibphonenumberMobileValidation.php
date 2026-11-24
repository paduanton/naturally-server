<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Application\MobilePhoneValidation;
use App\Modules\Identity\Domain\PhoneNumber;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

final class LibphonenumberMobileValidation implements MobilePhoneValidation
{
    public function accepts(PhoneNumber $number, string $countryCode): bool
    {
        $numbers = PhoneNumberUtil::getInstance();
        try { $parsed = $numbers->parse($number->value, null); }
        catch (NumberParseException) { return false; }

        return $numbers->isValidNumberForRegion($parsed, $countryCode)
            && in_array($numbers->getNumberType($parsed), [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE], true);
    }
}
