<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\RecoveryRequestInput;
use App\Modules\Identity\Application\RequestPasswordRecovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequestRecovery
{
    public function __construct(private RequestPasswordRecovery $requestRecovery) {}

    public function __invoke(Request $request): Response
    {
        $data = Validator::make($request->json()->all(), ['email' => 'required|string|email:rfc|max:255'])->validate();
        try { $this->requestRecovery->handle(new RecoveryRequestInput($data['email'])); }
        catch (\InvalidArgumentException) { throw ValidationException::withMessages(['email' => ['The field is invalid.']]); }
        return new Response('', 202, ['Cache-Control' => 'no-store']);
    }
}
