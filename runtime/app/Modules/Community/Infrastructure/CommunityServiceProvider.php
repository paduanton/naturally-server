<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure;

use Illuminate\Support\ServiceProvider;

final class CommunityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
