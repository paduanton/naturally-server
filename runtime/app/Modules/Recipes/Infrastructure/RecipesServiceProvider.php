<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure;

use Illuminate\Support\ServiceProvider;

final class RecipesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
