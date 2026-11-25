<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\CurrentSessionRevocationDenied;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\RevokeSession;
use App\Modules\Identity\Application\SessionActor;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class RevokeSessionEndpoint
{
    public function __construct(private RevokeSession $sessions) {}
    public function __invoke(SessionRevocationRequest $request, string $session): Response
    {
        try { $this->sessions->handle(new SessionActor((int) $request->user()->getAuthIdentifier(), $request->session()->getId()), $session); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        catch (CurrentSessionRevocationDenied) { abort(403); }
        return new Response('', 204, ['Cache-Control' => 'no-store']);
    }
}
