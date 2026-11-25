<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\AccountDetailsUnavailable;
use App\Modules\Identity\Application\CredentialChangeDenied;
use App\Modules\Identity\Application\EmailMutation;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Domain\EmailAddress;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class MysqlEmailMutation implements EmailMutation
{
    public function replaceForSession(int $actorId, string $sessionId, EmailAddress $email): bool
    {
        try {
            return DB::transaction(static function () use ($actorId, $sessionId, $email): bool {
                $user = User::query()->lockForUpdate()->find($actorId);
                $session = DB::table('sessions')->where('user_id', $actorId)->where('id', $sessionId)->lockForUpdate()->first(['id']);
                if ($user === null || $session === null) { throw new InactiveSession(); }
                if ($user->password === null) { throw new CredentialChangeDenied(); }
                if ($user->email === $email->value) { return false; }
                $old = $user->email;
                $user->forceFill(['email' => $email->value, 'email_verified_at' => null])->save();
                DB::table('account_restoration_tokens')->whereIn('email', [$old, $email->value])->delete();
                DB::table('password_reset_tokens')->where('email', $old)->delete();
                DB::table('sessions')->where('user_id', $actorId)->delete();
                return true;
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) { throw new AccountDetailsUnavailable(); }
            throw $exception;
        }
    }
}
