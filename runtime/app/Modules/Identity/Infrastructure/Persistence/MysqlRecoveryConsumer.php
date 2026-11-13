<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\RecoveryConsumer;
use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Domain\RecoveryToken;
use App\Modules\Identity\Domain\NewPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

final class MysqlRecoveryConsumer implements RecoveryConsumer
{
    public function consume(EmailAddress $email, #[\SensitiveParameter] RecoveryToken $token, #[\SensitiveParameter] NewPassword $password): bool
    {
        return DB::transaction(static function () use ($email, $token, $password): bool {
            $user = User::query()->where('email', $email->value)->lockForUpdate()->first();
            if ($user === null || $user->password === null) { return false; }
            $record = DB::table('password_reset_tokens')->where('email', $user->email)->lockForUpdate()->first();
            if ($record === null || $record->created_at === null) { return false; }
            $createdAt = Date::parse($record->created_at)->getTimestamp();
            $now = Date::now()->getTimestamp();
            if ($createdAt > $now || $now - $createdAt >= 1800 || !Hash::check($token->reveal(), $record->token)) { return false; }
            $user->forceFill(['password' => Hash::make($password->reveal())])->save();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            return true;
        });
    }
}
