<?php

declare(strict_types=1);

use App\Modules\Identity\Application as Cases;
use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    $prefix = $input['prefix'] ?? null;
    if (!is_string($prefix) || !preg_match('/\Afoundation_[a-f0-9]{16}_\z/D', $prefix)) {
        throw new RuntimeException();
    }

    $app = RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
    $app->make(Kernel::class)->bootstrap();
    if (!$app->environment('testing')) {
        throw new RuntimeException();
    }

    $app->make('config')->set('database.connections.mysql.prefix', $prefix);
    $app->make('config')->set('mail.default', 'array');
    $app->make('db')->purge('mysql');
    $announced = false;
    $app->make('db')->connection()->beforeExecuting(static function (string $query) use (&$announced): void {
        if (!$announced && str_contains(strtolower($query), 'for update') && str_contains($query, 'users')) {
            $announced = true;
            echo "WAITING\n";
            flush();
        }
    });

    match ($input['action']) {
        'restore' => $app->make(Cases\RestoreAccount::class)->handle(
            new Cases\AccountRestoreInput($input['email'], $input['token'], $input['password']),
        ),
        'email' => $app->make(Cases\ChangeEmail::class)->handle(
            new Cases\EmailChangeInput($input['actor'], $input['session'], $input['email']),
        ),
        'delete' => $app->make(Cases\DeleteAccount::class)->handle(
            new Cases\AccountDeletionInput($input['actor'], $input['session']),
        ),
        'session-revoke' => $app->make(Cases\RevokeSession::class)->handle(
            new Cases\SessionActor($input['actor'], $input['session']),
            $input['reference'],
        ),
        'social-remove' => $app->make(Cases\ManageSocialAccounts::class)->remove(
            new Cases\SessionActor($input['actor'], $input['session']),
            $input['account'],
        ),
        default => throw new RuntimeException(),
    };

    echo "ACCEPTED\n";
} catch (Cases\RestorationDenied|Cases\InactiveSession|Cases\SocialAccountRemovalConflict) {
    echo "REJECTED\n";
} catch (Throwable) {
    fwrite(STDERR, "Account worker failed.\n");
    exit(1);
}
