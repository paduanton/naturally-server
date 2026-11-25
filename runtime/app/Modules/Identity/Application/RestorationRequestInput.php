<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class RestorationRequestInput
{
    public function __construct(public string $email) {}
}
