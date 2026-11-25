<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

final class SessionReferenceEncoder
{
    public static function encode(int $owner, #[\SensitiveParameter] string $sessionId): string
    {
        return hash_hmac('sha256', 'session|'.$owner.'|'.$sessionId, config('app.key'));
    }
}
