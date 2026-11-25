<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface SocialAccountStore
{
    /** @return list<SocialAccountData> */
    public function listForSession(SessionActor $actor): array;

    public function removeForSession(SessionActor $actor, int $accountId): void;
}
