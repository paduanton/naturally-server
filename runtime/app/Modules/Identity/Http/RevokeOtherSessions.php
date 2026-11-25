<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\SessionRevocation;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RevokeOtherSessions
{
    public function __construct(private SessionRevocation $sessions) {}

    public function __invoke(Request $request): Response
    {
        try {
            $this->sessions->revokeOthers((int) $request->user()->getAuthIdentifier(), $request->session()->getId());
        } catch (InactiveSession) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new AuthenticationException();
        }
        return new Response('', 204, ['Cache-Control' => 'no-store']);
    }
}
