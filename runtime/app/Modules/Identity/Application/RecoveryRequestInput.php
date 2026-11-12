<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class RecoveryRequestInput
{
    public function __construct(public string $email) {}
}
