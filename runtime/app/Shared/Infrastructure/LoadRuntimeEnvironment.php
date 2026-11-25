<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use RuntimeException;

final readonly class LoadRuntimeEnvironment
{
    public function __construct(private string $publicPath, private string $secretDirectory) {}

    public function bootstrap(Application $application): void
    {
        // Startup-generated, protected configuration caching is a later increment.
        if ($application->configurationIsCached()) {
            throw new RuntimeException('Prebuilt runtime configuration cache is not supported.');
        }

        $settings = RuntimeConfiguration::load($this->publicPath, $this->secretDirectory);
        $application->instance(RuntimeConfiguration::class, $settings);
        $application->dontMergeFrameworkConfiguration();

        $environment = $settings->environment;
        $application->afterBootstrapping(LoadConfiguration::class,
            static function (Application $application) use ($environment): void {
                if ($application->environment() !== $environment) {
                    throw new RuntimeException('Runtime environment overrides are not supported.');
                }
            });
    }
}
