<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\CredentialChangeDenied;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\PasswordMutation;
use App\Modules\Identity\Domain\NewPassword;
use Illuminate\Support\Facades\DB;

final class MysqlPasswordMutation implements PasswordMutation
{
    public function replaceForSession(int $actorId, string $sessionId, NewPassword $password): void
    {
        DB::transaction(static function () use ($actorId, $sessionId, $password): void {
            $user = User::query()->lockForUpdate()->find($actorId);
            $session = DB::table('sessions')->where('user_id', $actorId)->where('id', $sessionId)->lockForUpdate()->first(['id']);
            if ($user === null || $session === null) { throw new InactiveSession(); }
            if ($user->password === null) { throw new CredentialChangeDenied(); }
            $user->forceFill(['password' => $password->reveal()])->save();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $actorId)->delete();
        });
    }
}
