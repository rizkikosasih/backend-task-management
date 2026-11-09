<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureRateLimiterCacheStore();
        $this->configureRateLimiters();
    }

    protected function configureRateLimiterCacheStore(): void
    {
        $defaultStore = (string) config('cache.default', 'database');

        if ($defaultStore === 'redis') {
            try {
                if (extension_loaded('redis')) {
                    Redis::connection('cache')->ping();
                    config(['cache.limiter' => 'redis']);

                    return;
                }
            } catch (Throwable) {
                config(['cache.limiter' => 'database']);

                return;
            }
        }

        config(['cache.limiter' => $defaultStore]);
    }

    protected function resolveRequestUser(Request $request): ?User
    {
        $bearer = $request->bearerToken();
        if ($bearer) {
            /** @var class-string<PersonalAccessToken> $tokenModel */
            $tokenModel = Sanctum::$personalAccessTokenModel;
            $personalAccessToken = $tokenModel::findToken($bearer);
            if ($personalAccessToken && $personalAccessToken->tokenable instanceof User) {
                return $personalAccessToken->tokenable;
            }
        }

        $user = $request->user();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('api-auth', function (Request $request) {
            $key = $request->ip().'|'.strtolower((string) $request->input('email', ''));

            return Limit::perMinute(10)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return ApiResponse::error(
                        'Batas frekuensi percobaan autentikasi terlampaui. Silakan tunggu beberapa saat.',
                        null,
                        429,
                        $headers,
                    );
                });
        });

        RateLimiter::for('api-oauth', function (Request $request) {
            $user = $this->resolveRequestUser($request);
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return Limit::perMinute(15)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return ApiResponse::error(
                        'Batas frekuensi permintaan OAuth terlampaui. Silakan tunggu beberapa saat.',
                        null,
                        429,
                        $headers,
                    );
                });
        });

        RateLimiter::for('api-mutations', function (Request $request) {
            $user = $this->resolveRequestUser($request);
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return Limit::perMinute(60)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return ApiResponse::error(
                        'Batas frekuensi mutasi data terlampaui. Silakan tunggu beberapa saat.',
                        null,
                        429,
                        $headers,
                    );
                });
        });

        RateLimiter::for('api-reads', function (Request $request) {
            $user = $this->resolveRequestUser($request);
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return Limit::perMinute(120)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return ApiResponse::error(
                        'Batas frekuensi pembacaan data terlampaui. Silakan tunggu beberapa saat.',
                        null,
                        429,
                        $headers,
                    );
                });
        });
    }
}
