<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Http\ProblemDetailsRenderer;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RuntimeApplicationFactory
{
    public static function create(string $basePath, string $publicPath, string $secretDirectory, string $runtimeDirectory): Application
    {
        foreach (['bootstrap/cache', 'storage/framework/views'] as $directory) {
            $path = $runtimeDirectory.'/'.$directory;
            if (!is_dir($path) && !@mkdir($path, 0700, true)) {
                throw new \RuntimeException('Writable runtime directory is unavailable.');
            }
        }

        $application = (new ApplicationBuilder(new RuntimeApplication($basePath, $runtimeDirectory)))
            ->withKernels()
            ->withEvents(discover: false)
            ->withCommands()
            ->withProviders()
            ->withRouting(using: static function () use ($basePath): void {
                require $basePath.'/routes/health.php';
            })
            ->withMiddleware()
            ->withExceptions(static function (Exceptions $exceptions): void {
                $renderer = new ProblemDetailsRenderer();
                // Laravel's callable-to-closure conversion loses method identity in Xdebug branch reports.
                $exceptions->render(static fn (Throwable $exception, Request $request) => $renderer->__invoke($exception, $request));
                $exceptions->report(static function (Throwable $exception): bool {
                    Log::error('Unhandled application exception.', ['exception_type' => $exception::class]);
                    return false;
                });
            })->create();

        $application->useStoragePath($runtimeDirectory.'/storage');
        $application->instance(LoadEnvironmentVariables::class, new LoadRuntimeEnvironment($publicPath, $secretDirectory));
        $application->make('events')->listen(CommandStarting::class, new RejectUnsafeRuntimeCommand());
        $application->afterResolving(ConsoleKernelContract::class, static function (ConsoleKernel $kernel): void {
            // Security listeners must run in testing as well as local/production.
            $kernel->rerouteSymfonyCommandEvents();
        });

        return $application;
    }

    public static function fromEnvironment(string $basePath): Application
    {
        $paths = [];
        foreach (['NATURALLY_CONFIG_PATH', 'NATURALLY_SECRET_DIRECTORY', 'NATURALLY_RUNTIME_DIRECTORY'] as $name) {
            $value = getenv($name);
            if ($value === false || trim($value) === '') {
                throw new \RuntimeException('Runtime configuration locations are required.');
            }
            $paths[] = $value;
        }

        return self::create($basePath, ...$paths);
    }
}
