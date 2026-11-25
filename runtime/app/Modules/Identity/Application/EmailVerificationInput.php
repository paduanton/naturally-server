<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class EmailVerificationInput
{
    public function __construct(public int $actorId, public int $targetId, public string $emailDigest) {}
}
