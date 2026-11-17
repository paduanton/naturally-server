<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\RequestAccountRestoration;
use App\Modules\Identity\Application\RestorationRequestInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequestRestoration
{
    public function __construct(private RequestAccountRestoration $restoration) {}
    public function __invoke(Request $request): Response
    {
        $data = Validator::make(
            ['input' => $request->json()->all()],
            ['input' => 'required|array:email', 'input.email' => 'required|string|email:rfc|max:255'],
        )->validate()['input'];

        try {
            $this->restoration->handle(new RestorationRequestInput($data['email']));
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['email' => ['Invalid email.']]);
        }

        return new Response('', 202);
    }
}
