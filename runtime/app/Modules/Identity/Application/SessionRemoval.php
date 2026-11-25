<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\SessionReference;

interface SessionRemoval
{
    public function removeForSession(SessionActor $actor, SessionReference $reference): void;
}
