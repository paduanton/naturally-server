<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\ListSessions;
use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Application\SessionData;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

final readonly class CurrentSessions
{
    public function __construct(private ListSessions $sessions) {}

    public function __invoke(ListSessionsRequest $request): JsonResponse
    {
        $pagination = $request->validated('pagination');
        try { $page = $this->sessions->handle(new SessionActor((int) $request->user()->getAuthIdentifier(), $request->session()->getId()), (int) ($pagination['page'] ?? 1), (int) ($pagination['per_page'] ?? 20)); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        return new JsonResponse(['data' => array_map(static fn (SessionData $session): array => ['reference' => $session->reference, 'last_seen_at' => $session->lastSeenAt, 'current' => $session->current], $page->data), 'meta' => ['current_page' => $page->page, 'per_page' => $page->perPage, 'total' => $page->total]], 200, ['Cache-Control' => 'no-store']);
    }
}
