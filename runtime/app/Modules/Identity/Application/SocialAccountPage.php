<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class SocialAccountPage
{
    /** @param list<SocialAccountData> $data */
    public function __construct(public array $data, public int $page, public int $perPage, public int $total) {}
}
