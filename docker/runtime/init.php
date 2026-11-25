<?php

declare(strict_types=1);

require __DIR__.'/DevelopmentVolumes.php';

try {
    (new DevelopmentVolumes(
        $argv[1] ?? '/run/app-secrets',
        $argv[2] ?? '/run/db-secrets',
        $argv[3] ?? '/run/runtime',
        $argv[4] ?? '/run/vendor',
        $argv[5] ?? '/run/db-data',
    ))->initialize();
} catch (Throwable) {
    fwrite(STDERR, "Development volume initialization failed; existing values were preserved.\n");
    exit(1);
}

fwrite(STDOUT, "Development volumes are ready. Existing values were preserved.\n");
