<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Domain\LoginIdentifier;
use Illuminate\Auth\EloquentUserProvider;

final class LoginUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        if (isset($credentials['email'])) {
            if (!is_string($credentials['email'])) { return null; }
            try { $identifier = LoginIdentifier::fromString($credentials['email']); }
            catch (\InvalidArgumentException) { return null; }
            unset($credentials['email']);
            $credentials[$identifier->column] = $identifier->value;
        }
        return parent::retrieveByCredentials($credentials);
    }
}
