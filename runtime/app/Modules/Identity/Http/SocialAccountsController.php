<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\ManageSocialAccounts;
use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Application\SocialAccountData;
use App\Modules\Identity\Application\SocialAccountRemovalConflict;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class SocialAccountsController
{
    public function __construct(private ManageSocialAccounts $accounts) {}

    public function index(ListSocialAccountsRequest $request): JsonResponse
    {
        $input = $request->validated('pagination');
        $actor = new SessionActor((int) $request->user()->getAuthIdentifier(), $request->session()->getId());

        try {
            $page = $this->accounts->listing($actor, (int) ($input['page'] ?? 1), (int) ($input['per_page'] ?? 20));
        } catch (InactiveSession) {
            throw new AuthenticationException();
        }

        return new JsonResponse([
            'data' => array_map(static fn (SocialAccountData $account): array => [
                'id' => $account->id,
                'provider' => $account->provider,
                'linked_at' => $account->linkedAt,
            ], $page->data),
            'meta' => [
                'current_page' => $page->page,
                'per_page' => $page->perPage,
                'total' => $page->total,
            ],
        ]);
    }

    public function destroy(SocialAccountRemovalRequest $request, string $account): Response
    {
        $id = filter_var($account, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404);
        $actor = new SessionActor((int) $request->user()->getAuthIdentifier(), $request->session()->getId());

        try {
            $this->accounts->remove($actor, $id);
        } catch (InactiveSession) {
            throw new AuthenticationException();
        } catch (SocialAccountRemovalConflict) {
            abort(409);
        }

        return new Response('', 204);
    }
}
