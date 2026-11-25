<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class SessionReference
{
    public function __construct(public string $value)
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $value)) { throw new \InvalidArgumentException('Invalid session reference.'); }
    }
}
