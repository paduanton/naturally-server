<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class SocialAccountData
{
    public function __construct(public int $id, public string $provider, public ?string $linkedAt) {}
}
