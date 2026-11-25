<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Illuminate\Foundation\Application;

final class RuntimeApplication extends Application
{
    private readonly string $runtimeCacheDirectory;

    public function __construct(string $basePath, string $runtimeDirectory)
    {
        $this->runtimeCacheDirectory = $runtimeDirectory.'/bootstrap/cache';
        parent::__construct($basePath);
    }

    /** Cache files use the mounted runtime directory; bootstrap source stays immutable. */
    protected function normalizeCachePath($key, $default): string
    {
        return $this->runtimeCacheDirectory.'/'.basename($default);
    }
}
