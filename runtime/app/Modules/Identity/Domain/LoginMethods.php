<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final class LoginMethods
{
    public static function assertRemovable(bool $hasLocalPassword, int $socialMethods): void
    {
        if (!$hasLocalPassword && $socialMethods <= 1) {
            throw new LastLoginMethodRemovalDenied();
        }
    }
}
