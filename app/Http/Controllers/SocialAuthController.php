<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\OAuthProvider;
use App\Services\AuthService;
use App\Services\OAuthService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function __construct(
        protected OAuthService $oauthService,
        protected AuthService $authService,
    ) {}

    /**
     * Redirect to OAuth provider
     * GET /api/oauth/{provider}/redirect
     */
    public function redirect(string $provider)
    {
        $validProviders = ['google', 'github'];

        if (!in_array($provider, $validProviders)) {
            return ApiResponse::error('Invalid OAuth provider', null, 400);
        }

        try {
            $redirectUrl = $this->oauthService->getRedirectUrl($provider);

            return ApiResponse::success([
                'redirect_url' => $redirectUrl,
                'url' => $redirectUrl,
            ]);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    /**
     * Handle OAuth callback
     * GET /api/oauth/{provider}/callback?code=...&state=...
     */
    public function callback(string $provider, Request $request)
    {
        try {
            // Step 1: Get user data from OAuth provider
            $oauthData = $this->oauthService->handleCallback($provider);

            $email = $oauthData['email'];
            if (empty($email)) {
                if ($request->wantsJson()) {
                    return ApiResponse::error(
                        'Alamat email publik tidak tersedia dari penyedia OAuth.',
                        ['email' => ['Alamat email publik diperlukan untuk autentikasi.']],
                        422
                    );
                }

                $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5101'), '/');

                return redirect()->to("{$frontendUrl}/login?error=oauth_email_missing&provider={$provider}");
            }

            // Step 2: Login or register user
            $result = $this->authService->loginOrRegisterViaOAuth(
                provider: $oauthData['provider'],
                providerId: $oauthData['provider_id'],
                email: (string) $email,
                name: $oauthData['name'] ?? 'Pengguna OAuth',
                profileData: $oauthData['profile_data'],
            );

            // Step 3: Log linking event if a new provider was linked to an existing account
            if ($result['is_new_provider']) {
                Log::info('Account linked via OAuth callback', [
                    'user_id' => $result['user']->id,
                    'provider' => $provider,
                    'provider_id' => $oauthData['provider_id'],
                    'ip' => $request->ip(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }

            if (!$request->wantsJson()) {
                $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5101'), '/');

                return redirect()->to("{$frontendUrl}/oauth/callback?token={$result['token']}");
            }

            $statusCode = $result['is_new_account'] ? 201 : 200;
            $message = match (true) {
                $result['is_new_account'] => 'Account created and logged in successfully',
                $result['is_new_provider'] => $result['message'] ?? 'Account linked and logged in',
                default => 'Logged in successfully',
            };

            return ApiResponse::success(
                [
                    'user' => $result['user'],
                    'token' => $result['token'],
                    'is_new_account' => $result['is_new_account'],
                    'is_new_provider' => $result['is_new_provider'] ?? false,
                ],
                $message,
                $statusCode,
            );
        } catch (Exception $e) {
            if (!$request->wantsJson()) {
                $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5101'), '/');

                return redirect()->to("{$frontendUrl}/login?error=oauth_failed");
            }

            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    /**
     * Link additional OAuth provider to existing account
     * POST /api/oauth/{provider}/link
     */
    public function link(string $provider, Request $request)
    {
        $user = $request->user();

        if ($user->hasOAuthProvider($provider)) {
            return ApiResponse::error(
                "Account already linked to {$provider}",
                null,
                400
            );
        }

        try {
            $oauthData = $this->oauthService->handleCallback($provider);

            // Check if this provider_id is already linked to another user
            $existingLink = OAuthProvider::where('provider', $provider)
                ->where('provider_id', $oauthData['provider_id'])
                ->first();

            if ($existingLink && $existingLink->user_id !== $user->id) {
                return ApiResponse::error(
                    'This account is already linked to another user',
                    null,
                    400
                );
            }

            // Link provider
            $user->oauthProviders()->create([
                'provider' => $oauthData['provider'],
                'provider_id' => $oauthData['provider_id'],
                'email' => $oauthData['email'],
                'profile_data' => $oauthData['profile_data'],
            ]);

            $user->update(['is_oauth_user' => true]);

            Log::info('Account linked manually via OAuth route', [
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $oauthData['provider_id'],
                'ip' => $request->ip(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return ApiResponse::success(
                ['user' => $user],
                "{$provider} linked successfully",
            );
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }
}
