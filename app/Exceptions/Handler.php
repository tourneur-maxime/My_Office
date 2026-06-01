<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    // public function render($request, Throwable $exception)
    // {
    //     if ($request->expectsJson() && $exception instanceof ValidationException) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation Error', // Generic message, can be more specific
    //             'errors' => $exception->errors(),
    //             'code' => 'VALIDATION_ERROR',
    //         ], $exception->status);
    //     }

    //     return parent::render($request, $exception);
    // }
}
