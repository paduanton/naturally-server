<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\AccountReader;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CurrentAccount
{
    public function __construct(private AccountReader $accounts) {}

    public function __invoke(Request $request): JsonResponse
    {
        $account = $this->accounts->forActor((int) $request->user()->getAuthIdentifier());
        if ($account === null) { throw new AuthenticationException(); }
        return new JsonResponse(['data' => ['id' => $account->id, 'name' => $account->name, 'username' => $account->username, 'email' => $account->email, 'email_verified' => $account->verified]], 200, ['Cache-Control' => 'no-store']);
    }
}
