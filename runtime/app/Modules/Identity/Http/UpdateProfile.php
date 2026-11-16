<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\AccountDetailsUnavailable;
use App\Modules\Identity\Application\ChangeProfile;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\ProfileChangeInput;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class UpdateProfile
{
    public function __construct(private ChangeProfile $profiles) {}

    public function __invoke(Request $request): Response
    {
        $data = Validator::make(['profile' => $request->json()->all()], ['profile' => 'required|array:name,username', 'profile.name' => 'sometimes|required|string|max:255', 'profile.username' => 'sometimes|required|string|min:4|max:20'])->validate()['profile'];
        try { $this->profiles->handle(new ProfileChangeInput((int) $request->user()->getAuthIdentifier(), $request->session()->getId(), $data['name'] ?? null, $data['username'] ?? null)); }
        catch (InactiveSession) { throw new AuthenticationException(); }
        catch (AccountDetailsUnavailable|\InvalidArgumentException) { throw ValidationException::withMessages(['profile' => ['Profile details are unavailable.']]); }
        return new Response('', 204);
    }
}
