<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\PasswordResetInput;
use App\Modules\Identity\Application\RecoveryDenied;
use App\Modules\Identity\Application\ResetPassword as ResetPasswordCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResetPassword
{
    public function __construct(private ResetPasswordCase $reset, private PasswordChangedResponse $signedOut) {}

    public function __invoke(Request $request): Response
    {
        $data = Validator::make($request->json()->all(), ['email' => 'required|string|email:rfc|max:255', 'token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/D'], 'password' => 'required|string|min:15|max:1024|confirmed'])->validate();
        try { $this->reset->handle(new PasswordResetInput($data['email'], $data['token'], $data['password'])); }
        catch (RecoveryDenied|\InvalidArgumentException) { throw ValidationException::withMessages(['token' => ['Recovery proof is invalid.']]); }
        return $this->signedOut->toResponse($request);
    }
}
