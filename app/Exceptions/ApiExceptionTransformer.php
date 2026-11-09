<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ApiExceptionTransformer
{
    public static function render(Throwable $e, Request $request): JsonResponse
    {
        return match (true) {
            $e instanceof HttpResponseException => $e->getResponse() instanceof JsonResponse
                ? $e->getResponse()
                : new JsonResponse($e->getResponse()->getContent(), $e->getResponse()->getStatusCode(), $e->getResponse()->headers->all(), 0, true),

            $e instanceof ValidationException => self::jsonResponse(
                422,
                'The given data was invalid.',
                $e->errors(),
            ),

            $e instanceof AuthenticationException => self::jsonResponse(401, 'Unauthenticated.'),

            $e instanceof AuthorizationException || $e instanceof SpatieUnauthorizedException => self::jsonResponse(403, 'You do not have the required permissions.'),

            $e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException => self::jsonResponse(404, 'Resource not found.'),

            $e instanceof MethodNotAllowedHttpException => self::jsonResponse(
                405,
                'Method not allowed.',
            ),

            $e instanceof TooManyRequestsHttpException => self::handleThrottleError($e),

            $e instanceof QueryException => self::handleDatabaseError($e),

            $e instanceof BusinessException => self::jsonResponse(
                $e->getCode() ?: 422,
                $e->getMessage(),
                $e->getErrors(),
            ),

            default => self::handleFallback($e),
        };
    }

    private static function handleThrottleError(TooManyRequestsHttpException $e): JsonResponse
    {
        return ApiResponse::error(
            'Batas frekuensi permintaan terlampaui. Silakan tunggu beberapa saat.',
            null,
            429,
            $e->getHeaders(),
        );
    }

    private static function jsonResponse(int $code, string $message, mixed $errors = null): JsonResponse
    {
        return ApiResponse::error($message, $errors, $code);
    }

    private static function handleDatabaseError(QueryException $e): JsonResponse
    {
        $message = config('app.debug') ? $e->getMessage() : 'Internal Database Error.';

        return self::jsonResponse(500, $message);
    }

    private static function handleFallback(Throwable $e): JsonResponse
    {
        $code = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        return self::jsonResponse($code, $e->getMessage() ?: 'Server Error.');
    }
}
