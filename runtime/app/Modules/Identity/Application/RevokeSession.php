<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\SessionReference;

final readonly class RevokeSession
{
    public function __construct(private SessionRemoval $sessions) {}
    public function handle(SessionActor $actor, string $reference): void
    {
        $this->sessions->removeForSession($actor, new SessionReference($reference));
    }
}
