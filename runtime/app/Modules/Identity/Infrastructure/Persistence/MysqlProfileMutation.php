<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\AccountData;
use App\Modules\Identity\Application\AccountDetailsUnavailable;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\ProfileMutation;
use App\Modules\Identity\Domain\DisplayName;
use App\Modules\Identity\Domain\Username;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class MysqlProfileMutation implements ProfileMutation
{
    public function updateForSession(int $actorId, string $sessionId, ?DisplayName $name, ?Username $username): AccountData
    {
        try {
            return DB::transaction(static function () use ($actorId, $sessionId, $name, $username): AccountData {
                $user = User::query()->lockForUpdate()->find($actorId);
                $session = DB::table('sessions')->where('user_id', $actorId)->where('id', $sessionId)->lockForUpdate()->first(['id']);
                if ($user === null || $session === null) { throw new InactiveSession(); }
                if ($name !== null) { $user->forceFill(['name' => $name->value]); }
                if ($username !== null) { $user->forceFill(['username' => $username->value]); }
                $user->save();
                return new AccountData($user->id, $user->name, $user->username, $user->email, $user->email_verified_at !== null);
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) { throw new AccountDetailsUnavailable(); }
            throw $exception;
        }
    }
}
