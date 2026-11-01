<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200, mixed $meta = null): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
                'meta' => $meta,
            ],
            $code,
        );
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resource,
        string $message = 'Data retrieved successfully',
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_next_page' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public static function error(string $message = 'Error', mixed $errors = null, int $code = 400): JsonResponse
    {
        return response()->json(
            [
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ],
            $code,
        );
    }
}
