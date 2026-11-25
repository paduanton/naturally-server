<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class ListSessions
{
    public function __construct(private SessionInventory $sessions) {}

    public function handle(SessionActor $actor, int $page = 1, int $perPage = 20): SessionPage
    {
        if ($page < 1 || $page > 1000000 || $perPage < 1 || $perPage > 100) { throw new \InvalidArgumentException('Invalid session pagination.'); }
        return $this->sessions->forSession($actor, $page, $perPage);
    }
}
