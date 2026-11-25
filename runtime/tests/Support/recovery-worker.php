<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $input = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
    if (!preg_match('/\Afoundation_[a-f0-9]{16}_\z/D', $input['prefix'])) { throw new RuntimeException(); }
    $app = \App\Shared\Infrastructure\RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (!$app->environment('testing')) { throw new RuntimeException(); }
    $app->make('config')->set('database.connections.mysql.prefix', $input['prefix']);
    $app->make('db')->purge('mysql');
    $announced = false;
    $app->make('db')->connection()->beforeExecuting(static function (string $query) use (&$announced): void {
        if (!$announced && str_contains(strtolower($query), 'for update') && str_contains($query, 'users')) {
            $announced = true;
            echo "WAITING\n";
            flush();
        }
    });
    $app->make(\App\Modules\Identity\Application\ResetPassword::class)->handle(new \App\Modules\Identity\Application\PasswordResetInput($input['email'], $input['token'], $input['password']));
    echo "ACCEPTED\n";
} catch (\App\Modules\Identity\Application\RecoveryDenied) {
    echo "REJECTED\n";
} catch (Throwable) {
    fwrite(STDERR, "Recovery worker failed.\n");
    exit(1);
}
