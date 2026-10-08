<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Illuminate\Console\Events\CommandStarting;
use RuntimeException;

final class RejectUnsafeRuntimeCommand
{
    public function __invoke(CommandStarting $event): void
    {
        if (in_array($event->command, [
            'config:show', 'config:cache', 'env:encrypt', 'env:decrypt',
            'key:generate', 'optimize', 'db',
        ], true)) {
            throw new RuntimeException('Command is incompatible with mounted runtime configuration.');
        }
    }
}
