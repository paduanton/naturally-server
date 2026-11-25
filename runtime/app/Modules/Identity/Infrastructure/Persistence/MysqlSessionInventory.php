<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Application\SessionData;
use App\Modules\Identity\Application\SessionInventory;
use App\Modules\Identity\Application\SessionPage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class MysqlSessionInventory implements SessionInventory
{
    public function forSession(SessionActor $actor, int $page, int $perPage): SessionPage
    {
        return DB::transaction(static function () use ($actor, $page, $perPage): SessionPage {
            SessionOwnerLock::acquire($actor);
            $query = DB::table('sessions')->where('user_id', $actor->actorId)->where('last_activity', '>=', Date::now()->getTimestamp() - (config('session.lifetime') * 60));
            $total = $query->count();
            $data = $query->orderByDesc('last_activity')->orderBy('id')->forPage($page, $perPage)->get(['id', 'last_activity'])->map(static fn ($row): SessionData => new SessionData(SessionReferenceEncoder::encode($actor->actorId, $row->id), gmdate('c', (int) $row->last_activity), hash_equals($actor->sessionId(), $row->id)))->all();
            return new SessionPage($data, $page, $perPage, $total);
        });
    }
}
