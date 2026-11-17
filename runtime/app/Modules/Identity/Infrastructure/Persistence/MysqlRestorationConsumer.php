<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\RestorationConsumer;
use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\NewPassword;
use App\Modules\Identity\Domain\RecoveryToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Timebox;

final class MysqlRestorationConsumer implements RestorationConsumer
{
    public function consume(
        EmailAddress $email,
        #[\SensitiveParameter] RecoveryToken $token,
        #[\SensitiveParameter] NewPassword $password,
    ): ?int {
        return app(Timebox::class)->call(static function () use ($email, $token, $password): ?int {
            return DB::transaction(static function () use ($email, $token, $password): ?int {
                $user = User::withTrashed()->where('email', $email->value)->lockForUpdate()->first();
                if ($user === null || !$user->trashed() || $user->password === null) {
                    return null;
                }

                $record = DB::table('account_restoration_tokens')->where('email', $user->email)->lockForUpdate()->first();
                if ($record === null || $record->created_at === null) {
                    return null;
                }

                $createdAt = Date::parse($record->created_at)->getTimestamp();
                $now = Date::now()->getTimestamp();
                if ($createdAt > $now || $now - $createdAt >= 1800 || !Hash::check($token->reveal(), $record->token)) {
                    return null;
                }

                $user->forceFill(['password' => Hash::make($password->reveal())]);
                if (!$user->restore()) {
                    return null;
                }

                DB::table('account_restoration_tokens')->where('email', $user->email)->delete();
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();

                return (int) $user->id;
            });
        }, 200000);
    }
}
