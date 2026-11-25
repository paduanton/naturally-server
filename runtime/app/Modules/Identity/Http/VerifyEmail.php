<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\EmailVerificationDenied;
use App\Modules\Identity\Application\EmailVerificationInput;
use App\Modules\Identity\Application\VerifyEmail as VerifyEmailCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class VerifyEmail
{
    public function __construct(private VerifyEmailCase $verify) {}

    public function __invoke(Request $request, string $id, string $hash): Response
    {
        try { $this->verify->handle(new EmailVerificationInput((int) $request->user()->getAuthIdentifier(), (int) $id, $hash)); }
        catch (EmailVerificationDenied) { throw new HttpException(403); }
        return new Response('', 204, ['Cache-Control' => 'no-store']);
    }
}
