<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class RestorationTokenRepository extends DatabaseTokenRepository
{
    public function __construct()
    {
        parent::__construct(DB::connection(), Hash::driver(), 'account_restoration_tokens', config('app.key'), 1800, 60);
    }
}
