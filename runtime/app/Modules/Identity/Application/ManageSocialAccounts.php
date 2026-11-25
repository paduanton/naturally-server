<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\LastLoginMethodRemovalDenied;

final readonly class ManageSocialAccounts
{
    public function __construct(private SocialAccountStore $accounts) {}

    public function listing(SessionActor $actor, int $page = 1, int $perPage = 20): SocialAccountPage
    {
        if ($page < 1 || $page > 1000000 || $perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('Invalid social account pagination.');
        }

        return $this->accounts->listForSession($actor, $page, $perPage);
    }

    public function remove(SessionActor $actor, int $accountId): void
    {
        if ($accountId < 1) {
            throw new \InvalidArgumentException('Invalid social account.');
        }

        try {
            $this->accounts->removeForSession($actor, $accountId);
        } catch (LastLoginMethodRemovalDenied $denied) {
            throw new SocialAccountRemovalConflict('Removing the link would leave the account without a login method.', previous: $denied);
        }
    }
}
