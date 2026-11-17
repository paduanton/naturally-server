<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\AccountDetailsUnavailable;
use App\Modules\Identity\Application\ChangeEmail;
use App\Modules\Identity\Application\CredentialChangeDenied;
use App\Modules\Identity\Application\EmailChangeInput;
use App\Modules\Identity\Application\InactiveSession;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class UpdateEmail
{
    public function __construct(private ChangeEmail $emails, private PasswordChangedResponse $signedOut) {}

    public function __invoke(Request $request): Response
    {
        $data = Validator::make(['change' => $request->json()->all()], ['change' => 'required|array:email', 'change.email' => 'required|string|email:rfc|max:255'])->validate()['change'];
        try { $changed = $this->emails->handle(new EmailChangeInput((int) $request->user()->getAuthIdentifier(), $request->session()->getId(), $data['email'])); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        catch (AccountDetailsUnavailable|CredentialChangeDenied|\InvalidArgumentException) { throw ValidationException::withMessages(['email' => ['Email is unavailable.']]); }
        return $changed ? $this->signedOut->toResponse($request) : new Response('', 204);
    }
}
