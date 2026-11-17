<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\AccountRestoreInput;
use App\Modules\Identity\Application\RestorationDenied;
use App\Modules\Identity\Application\RestoreAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class ConsumeRestoration
{
    public function __construct(private RestoreAccount $restoration, private PasswordChangedResponse $signedOut) {}

    public function __invoke(Request $request): Response
    {
        $data = Validator::make(
            ['restoration' => $request->json()->all()],
            [
                'restoration' => 'required|array:email,token,password,password_confirmation',
                'restoration.email' => 'required|string|email:rfc|max:255',
                'restoration.token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/D'],
                'restoration.password' => 'required|string|min:15|max:1024|confirmed',
            ],
        )->validate()['restoration'];

        try {
            $this->restoration->handle(new AccountRestoreInput($data['email'], $data['token'], $data['password']));
        } catch (RestorationDenied|\InvalidArgumentException) {
            throw ValidationException::withMessages(['token' => ['Restoration proof is invalid.']]);
        }

        return $this->signedOut->toResponse($request);
    }
}
