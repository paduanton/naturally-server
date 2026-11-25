<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface SessionInventory
{
    public function forSession(SessionActor $actor, int $page, int $perPage): SessionPage;
}
