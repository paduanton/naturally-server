<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {

        if ($exception instanceof ModelNotFoundException) {
            $message = $exception->getMessage() ? $exception->getMessage() : 'There is no data';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'error' => 'Model not found in the server'
                ], 404);
            }

            return redirect('/v1/notfound');
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($exception instanceof NotFoundHttpException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid route',
                ], 404);
            }

            return redirect('/v1/fallback');
        }

        return parent::render($request, $exception);
    }
}
