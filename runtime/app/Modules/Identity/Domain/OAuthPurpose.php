<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

enum OAuthPurpose: string
{
    case Login = 'login';
    case Link = 'link';
    case Confirm = 'confirm';

    public function returnPath(): string
    {
        return $this === self::Login ? '/auth/complete' : '/settings/security';
    }
}
