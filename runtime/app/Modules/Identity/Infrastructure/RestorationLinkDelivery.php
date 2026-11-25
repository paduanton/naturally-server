<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Application\RestorationDelivery;
use App\Modules\Identity\Domain\EmailAddress;
use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Timebox;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class RestorationLinkDelivery implements RestorationDelivery
{
    public function requestForEmail(EmailAddress $email): void
    {
        app(Timebox::class)->call(static function () use ($email): void {
            DB::transaction(static function () use ($email): void {
                $user = User::onlyTrashed()->where('email', $email->value)->lockForUpdate()->first();
                if ($user === null || $user->password === null) {
                    return;
                }

                $repository = app(RestorationTokenRepository::class);
                if ($repository->recentlyCreatedToken($user)) {
                    return;
                }

                $token = $repository->create($user);
                DB::afterCommit(static function () use ($user, $token): void {
                    $sent = false;
                    app()->terminating(static function () use ($user, $token, &$sent): void {
                        if ($sent) {
                            return;
                        }
                        $sent = true;

                        try {
                            $user->notify(new RestoreAccountNotification($token));
                        } catch (TransportExceptionInterface) {
                            Log::warning('Restoration mail delivery unavailable.');
                        }
                    });
                });
            });
        }, 200000);
    }
}
