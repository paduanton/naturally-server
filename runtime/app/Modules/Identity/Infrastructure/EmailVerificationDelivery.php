<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Application\VerificationDelivery;
use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class EmailVerificationDelivery implements VerificationDelivery
{
    public function sendForActor(int $actorId): void
    {
        $user = User::find($actorId);
        if ($user === null || $user->hasVerifiedEmail()) { return; }
        try { $user->notify(new VerifyEmail()); }
        catch (TransportExceptionInterface) { Log::warning('Verification mail delivery unavailable.'); }
    }
}
