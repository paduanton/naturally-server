<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\ManagePhoneContacts;
use App\Modules\Identity\Application\PhoneContactSession;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class PhoneContactsController
{
    public function __construct(private ManagePhoneContacts $contacts) {}

    public function index(Request $request): JsonResponse
    {
        abort_if($request->query->count() > 0, 400);
        try { $contacts = $this->contacts->listing($this->context($request)); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        return PhoneContactResource::collection($contacts)->response();
    }

    public function store(PhoneContactRequest $request): JsonResponse
    {
        $data = $request->validated('contact');
        try { $contact = $this->contacts->add($this->context($request), $data['label'], $data['country_code'], $data['number'], $data['main']); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        catch (\DomainException|\InvalidArgumentException) { throw ValidationException::withMessages(['contact' => ['Phone contact is unavailable.']]); }
        return (new PhoneContactResource($contact))->response()->setStatusCode(201);
    }

    public function destroy(PhoneContactRequest $request, string $phone): Response
    {
        $id = $this->phoneId($phone);
        try { $this->contacts->remove($this->context($request), $id); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        return new Response('', 204);
    }

    public function update(PhoneContactRequest $request, string $phone): JsonResponse
    {
        $id = $this->phoneId($phone);
        $data = $request->validated('contact');
        try { $contact = $this->contacts->change($this->context($request), $id, $data['label'] ?? null, $data['main'] ?? null); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        catch (\App\Modules\Identity\Application\PhoneContactNotFound) { abort(404); }
        catch (\DomainException|\InvalidArgumentException) { throw ValidationException::withMessages(['contact' => ['Phone contact is unavailable.']]); }
        return (new PhoneContactResource($contact))->response();
    }

    private function phoneId(string $phone): int
    {
        $id = filter_var($phone, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404);
        return $id;
    }

    private function context(Request $request): PhoneContactSession
    {
        return new PhoneContactSession((int) $request->user()->getAuthIdentifier(), $request->session()->getId());
    }
}
