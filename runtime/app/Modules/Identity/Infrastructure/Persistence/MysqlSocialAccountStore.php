<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Application\SocialAccountData;
use App\Modules\Identity\Application\SocialAccountPage;
use App\Modules\Identity\Application\SocialAccountStore;
use App\Modules\Identity\Domain\LoginMethods;
use Illuminate\Support\Facades\DB;

final class MysqlSocialAccountStore implements SocialAccountStore
{
    public function listForSession(SessionActor $actor, int $page, int $perPage): SocialAccountPage
    {
        return DB::transaction(static function () use ($actor, $page, $perPage): SocialAccountPage {
            SessionOwnerLock::acquire($actor);

            $links = SocialAccount::query()->where('user_id', $actor->actorId);
            $total = $links->count();
            $data = $links
                ->orderBy('id')
                ->forPage($page, $perPage)
                ->get(['id', 'provider', 'created_at'])
                ->map(static fn (SocialAccount $account): SocialAccountData => new SocialAccountData(
                    $account->id,
                    $account->provider->value,
                    $account->created_at?->toIso8601String(),
                ))
                ->all();

            return new SocialAccountPage($data, $page, $perPage, $total);
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
