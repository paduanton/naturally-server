<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class PhoneContactData
{
    public function __construct(public int $id, public string $label, public string $countryCode, public string $number, public bool $main) {}
}
