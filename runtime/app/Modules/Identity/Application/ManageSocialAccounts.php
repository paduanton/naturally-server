<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class ManageSocialAccounts
{
    public function __construct(private SocialAccountStore $accounts) {}

    /** @return list<SocialAccountData> */
    public function listing(SessionActor $actor): array
    {
        return $this->accounts->listForSession($actor);
    }

    public function remove(SessionActor $actor, int $accountId): void
    {
        if ($accountId < 1) {
            throw new \InvalidArgumentException('Invalid social account.');
        }

        $this->accounts->removeForSession($actor, $accountId);
    }
}
