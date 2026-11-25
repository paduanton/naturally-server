<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\AccountDeletion;
use App\Modules\Identity\Application\InactiveSession;
use Illuminate\Support\Facades\DB;

final class MysqlAccountDeletion implements AccountDeletion
{
    public function deleteForSession(int $actorId, string $sessionId): void
    {
        DB::transaction(static function () use ($actorId, $sessionId): void {
            $user = User::query()->lockForUpdate()->find($actorId);
            $session = DB::table('sessions')->where('user_id', $actorId)->where('id', $sessionId)->lockForUpdate()->first(['id']);
            if ($user === null || $session === null) { throw new InactiveSession(); }
            $user->delete();
            DB::table('account_restoration_tokens')->where('email', $user->email)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $actorId)->delete();
        });
    }
}
