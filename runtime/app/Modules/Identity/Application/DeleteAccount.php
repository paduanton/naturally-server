<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class DeleteAccount
{
    public function __construct(private AccountDeletion $accounts) {}
    public function handle(AccountDeletionInput $input): void { $this->accounts->deleteForSession($input->actorId, $input->sessionId()); }
}
