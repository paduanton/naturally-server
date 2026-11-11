<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\EmailVerificationStore;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class MysqlEmailVerificationStore implements EmailVerificationStore
{
    public function verifyCurrentEmail(int $actorId, string $emailDigest): bool
    {
        return DB::transaction(static function () use ($actorId, $emailDigest): bool {
            $user = User::query()->lockForUpdate()->find($actorId);
            if ($user === null || !hash_equals(sha1($user->getEmailForVerification()), $emailDigest)) { return false; }
            if (!$user->hasVerifiedEmail()) {
                $user->forceFill(['email_verified_at' => Date::now()])->save();
                DB::afterCommit(static fn () => event(new Verified($user)));
            }
            return true;
        });
    }
}
