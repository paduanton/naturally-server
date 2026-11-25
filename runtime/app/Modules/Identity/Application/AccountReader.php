<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface AccountReader
{
    public function forActor(int $actorId): ?AccountData;
}
