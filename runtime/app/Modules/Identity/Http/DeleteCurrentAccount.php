<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\AccountDeletionInput;
use App\Modules\Identity\Application\DeleteAccount;
use App\Modules\Identity\Application\InactiveSession;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteCurrentAccount
{
    public function __construct(private DeleteAccount $accounts, private PasswordChangedResponse $signedOut) {}

    public function __invoke(Request $request): Response
    {
        if ($request->json()->all() !== []) { throw ValidationException::withMessages(['account' => ['No fields accepted.']]); }
        try { $this->accounts->handle(new AccountDeletionInput((int) $request->user()->getAuthIdentifier(), $request->session()->getId())); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        return $this->signedOut->toResponse($request);
    }
}
