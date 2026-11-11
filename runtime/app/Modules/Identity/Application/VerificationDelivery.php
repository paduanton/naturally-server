<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface VerificationDelivery
{
    public function sendForActor(int $actorId): void;
}
