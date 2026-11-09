<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->header('Idempotency-Key');

        if (!is_string($idempotencyKey) || $idempotencyKey === '') {
            return $next($request);
        }

        $user = $request->user();
        $userId = $user ? (string) $user->id : 'anonymous';
        $cacheKey = 'idempotency:'.sha1($userId.':'.$request->path().':'.$idempotencyKey);

        $cachedResponse = Cache::get($cacheKey);

        if (is_array($cachedResponse) && isset($cachedResponse['content'], $cachedResponse['status'])) {
            return response(
                (string) $cachedResponse['content'],
                (int) $cachedResponse['status'],
                ['Content-Type' => 'application/json', 'X-Cache' => 'HIT-IDEMPOTENT']
            );
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
            ], now()->addHours(24));
        }

        return $response;
    }
}
