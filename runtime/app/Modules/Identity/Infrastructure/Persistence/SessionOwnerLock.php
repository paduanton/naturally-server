<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\SessionActor;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class SessionOwnerLock
{
    public static function acquire(SessionActor $actor): void
    {
        $user = User::query()->lockForUpdate()->find($actor->actorId);
        $session = DB::table('sessions')->where('user_id', $actor->actorId)->where('id', $actor->sessionId())->lockForUpdate()->first(['last_activity']);
        if ($user === null || $session === null || $session->last_activity < Date::now()->getTimestamp() - (config('session.lifetime') * 60)) { throw new InactiveSession(); }
    }
}
