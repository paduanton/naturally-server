<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\SessionRevocation;
use Illuminate\Support\Facades\DB;

final class MysqlSessionRevocation implements SessionRevocation
{
    public function revokeOthers(int $actorId, string $currentSessionId): void
    {
        DB::transaction(static function () use ($actorId, $currentSessionId): void {
            $user = User::query()->lockForUpdate()->find($actorId);
            $session = DB::table('sessions')->where('user_id', $actorId)->where('id', $currentSessionId)->lockForUpdate()->first(['id']);
            if ($user === null || $session === null) { throw new InactiveSession(); }
            DB::table('sessions')->where('user_id', $actorId)->where('id', '<>', $currentSessionId)->delete();
        });
    }
}
