<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\VerificationDelivery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResendVerification
{
    public function __construct(private VerificationDelivery $delivery) {}

    public function __invoke(Request $request): Response
    {
        $this->delivery->sendForActor((int) $request->user()->getAuthIdentifier());
        return new Response('', 202, ['Cache-Control' => 'no-store']);
    }
}
