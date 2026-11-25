<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

interface SocialAccountStore
{
    public function listForSession(SessionActor $actor, int $page, int $perPage): SocialAccountPage;

    public function removeForSession(SessionActor $actor, int $accountId): void;
}
