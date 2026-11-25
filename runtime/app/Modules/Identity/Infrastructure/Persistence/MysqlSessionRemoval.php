<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\CurrentSessionRevocationDenied;
use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Application\SessionRemoval;
use App\Modules\Identity\Domain\SessionReference;
use Illuminate\Support\Facades\DB;

final class MysqlSessionRemoval implements SessionRemoval
{
    public function removeForSession(SessionActor $actor, SessionReference $reference): void
    {
        DB::transaction(static function () use ($actor, $reference): void {
            SessionOwnerLock::acquire($actor);
            if (hash_equals(SessionReferenceEncoder::encode($actor->actorId, $actor->sessionId()), $reference->value)) { throw new CurrentSessionRevocationDenied(); }
            $sessions = DB::table('sessions')->where('user_id', $actor->actorId)->where('id', '<>', $actor->sessionId())->select('id')->lazyById(100);
            foreach ($sessions as $session) {
                if (hash_equals(SessionReferenceEncoder::encode($actor->actorId, $session->id), $reference->value)) {
                    DB::table('sessions')->where('user_id', $actor->actorId)->where('id', $session->id)->delete();
                    return;
                }
            }
        });
    }
}
