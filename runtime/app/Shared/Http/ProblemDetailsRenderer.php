<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ProblemDetailsRenderer
{
    public function __invoke(Throwable $exception, Request $request): JsonResponse
    {
        $status = match (true) {
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            $exception instanceof ValidationException => 422,
            $exception instanceof AuthenticationException => 401,
            default => 500,
        };
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        $body = [
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Request Failed',
            'status' => $status,
        ];
        if ($request->is('api/v1/*')) {
            $body['code'] = match ($status) {
                401 => 'unauthenticated', 403 => 'forbidden', 404 => 'not_found',
                405 => 'method_not_allowed', 419 => 'csrf_mismatch', 422 => 'validation_failed', 423 => 'reauthentication_required',
                429 => 'rate_limited', 503 => 'service_unavailable', default => 'request_failed',
            };
            $body['correlation_id'] = $request->attributes->get('correlation_id') ?? bin2hex(random_bytes(16));
            $headers['X-Request-ID'] = $body['correlation_id'];
            if ($exception instanceof ValidationException) {
                $body['errors'] = array_fill_keys(array_keys($exception->errors()), ['The field is invalid.']);
            }
        }
        return new JsonResponse($body, $status, array_merge($headers, [
            'Content-Type' => 'application/problem+json',
            'Cache-Control' => 'no-store',
        ]));
    }
}
