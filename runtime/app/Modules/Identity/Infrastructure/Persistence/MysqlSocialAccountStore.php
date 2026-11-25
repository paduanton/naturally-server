<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Application\SocialAccountData;
use App\Modules\Identity\Application\SocialAccountStore;
use App\Modules\Identity\Domain\LoginMethods;
use Illuminate\Support\Facades\DB;

final class MysqlSocialAccountStore implements SocialAccountStore
{
    public function listForSession(SessionActor $actor): array
    {
        return DB::transaction(static function () use ($actor): array {
            SessionOwnerLock::acquire($actor);

            return SocialAccount::query()
                ->where('user_id', $actor->actorId)
                ->orderBy('id')
                ->get()
                ->map(static fn (SocialAccount $account): SocialAccountData => new SocialAccountData(
                    $account->id,
                    $account->provider->value,
                    $account->created_at?->toIso8601String(),
                ))
                ->all();
        });
    }

    public function removeForSession(SessionActor $actor, int $accountId): void
    {
        DB::transaction(static function () use ($actor, $accountId): void {
            $user = SessionOwnerLock::acquire($actor);
            $accounts = SocialAccount::query()
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $account = $accounts->firstWhere('id', $accountId);
            if ($account === null) {
                return;
            }

            LoginMethods::assertRemovable($user->password !== null, $accounts->count());
            $account->delete();
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '<>', $actor->sessionId())
                ->delete();
        });
    }
}
