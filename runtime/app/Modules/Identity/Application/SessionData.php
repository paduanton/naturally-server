<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class SessionData
{
    public function __construct(public string $reference, public string $lastSeenAt, public bool $current) {}
}
