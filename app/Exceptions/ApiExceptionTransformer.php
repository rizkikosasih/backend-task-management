<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Throwable;

class ApiExceptionTransformer
{
    public static function render(Throwable $e, Request $request)
    {
        return match (true) {
            $e instanceof ValidationException => self::jsonResponse(
                422,
                'The given data was invalid.',
                $e->errors(),
            ),

            $e instanceof AuthenticationException => self::jsonResponse(401, 'Unauthenticated.'),

            $e instanceof AuthorizationException || $e instanceof SpatieUnauthorizedException
                => self::jsonResponse(403, 'You do not have the required permissions.'),

            $e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException
                => self::jsonResponse(404, 'Resource not found.'),

            $e instanceof MethodNotAllowedHttpException => self::jsonResponse(
                405,
                'Method not allowed.',
            ),

            $e instanceof QueryException => self::handleDatabaseError($e),
            // Custom Business Logic Exception
            $e instanceof BusinessException => self::jsonResponse(
                code: $e->getCode() ?: 422,
                message: $e->getMessage(),
                errors: $e->getErrors(),
            ),

            default => self::handleFallback($e),
        };
    }

    private static function jsonResponse(int $code, string $message, $errors = null)
    {
        return ApiResponse::error($message, $errors, $code);
    }

    private static function handleDatabaseError($e)
    {
        // Jangan tampilkan detail query di production
        $message = config('app.debug') ? $e->getMessage() : 'Internal Database Error.';
        return self::jsonResponse(500, $message);
    }

    private static function handleFallback($e)
    {
        $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        return self::jsonResponse($code, $e->getMessage() ?: 'Server Error.');
    }
}
