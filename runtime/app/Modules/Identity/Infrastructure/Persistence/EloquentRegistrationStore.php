<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\AccountData;
use App\Modules\Identity\Application\NewAccountData;
use App\Modules\Identity\Application\RegistrationConflict;
use App\Modules\Identity\Application\RegistrationStore;
use Illuminate\Database\QueryException;

final class EloquentRegistrationStore implements RegistrationStore
{
    public function register(NewAccountData $account): AccountData
    {
        try {
            $user = new User();
            $user->forceFill(['name' => $account->name, 'username' => $account->username->value, 'email' => $account->email->value, 'password' => $account->password->reveal()]);
            $user->save();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new RegistrationConflict();
            }
            throw $exception;
        }
        return new AccountData($user->id, $user->name, $user->username, $user->email, $user->email_verified_at !== null);
    }
}
