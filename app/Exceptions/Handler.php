<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidTransactionException;
use App\Exceptions\AccountNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\QueryException;

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

        // Handle custom exceptions
        $this->renderable(function (InsufficientFundsException $e) {
            return response()->json($e->render(), Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $this->renderable(function (InvalidTransactionException $e) {
            return response()->json($e->render(), Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $this->renderable(function (AccountNotFoundException $e) {
            return response()->json($e->render(), Response::HTTP_NOT_FOUND);
        });

        // Handle authentication exceptions
        $this->renderable(function (AuthenticationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'data' => ['code' => 'UNAUTHENTICATED']
            ], Response::HTTP_UNAUTHORIZED);
        });

        // Handle validation exceptions
        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The given data was invalid',
                'errors' => $e->errors(),
                'data' => ['code' => 'VALIDATION_ERROR']
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        // Handle model not found exceptions
        $this->renderable(function (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
                'data' => [
                    'model' => class_basename($e->getModel()),
                    'code' => 'RESOURCE_NOT_FOUND'
                ]
            ], Response::HTTP_NOT_FOUND);
        });

        // Handle 404 errors
        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The requested resource was not found',
                'data' => ['code' => 'NOT_FOUND']
            ], Response::HTTP_NOT_FOUND);
        });

        // Handle method not allowed errors
        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Method not allowed',
                'data' => ['code' => 'METHOD_NOT_ALLOWED']
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        });

        // Handle database query exceptions
        $this->renderable(function (QueryException $e) {
            $message = 'Database error occurred';
            $code = 'DATABASE_ERROR';

            // Check for unique constraint violations
            if ($e->getCode() === '23000') {
                $message = 'A record with this information already exists';
                $code = 'DUPLICATE_ENTRY';
            }

            return response()->json([
                'status' => 'error',
                'message' => $message,
                'data' => [
                    'code' => $code,
                    'details' => app()->environment('local') ? $e->getMessage() : null
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        // Handle any other exceptions
        $this->renderable(function (Throwable $e) {
            if (!app()->environment('production')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'data' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(),
                        'code' => 'INTERNAL_ERROR'
                    ]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'data' => ['code' => 'INTERNAL_ERROR']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Validation\ValidationException $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'status' => 'error',
            'message' => $exception->getMessage(),
            'errors' => $this->transformErrors($exception),
            'data' => ['code' => 'VALIDATION_ERROR']
        ], $exception->status);
    }

    /**
     * Transform the error messages.
     *
     * @param \Illuminate\Validation\ValidationException $exception
     * @return array
     */
    protected function transformErrors(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->errors() as $field => $message) {
            $errors[$field] = $message[0] ?? 'Invalid value';
        }

        return $errors;
    }
}
