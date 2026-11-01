<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class OAuthService
{
    public function getRedirectUrl(string $provider): string
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->stateless()->redirect()->getTargetUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function handleCallback(string $provider): array
    {
        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            $oauthUser = $driver->stateless()->user();

            $raw = method_exists($oauthUser, 'getRaw') ? $oauthUser->getRaw() : [];

            return [
                'provider' => $provider,
                'provider_id' => $oauthUser->getId(),
                'email' => $oauthUser->getEmail(),
                'name' => $oauthUser->getName(),
                'profile_data' => [
                    'avatar' => $oauthUser->getAvatar(),
                    'nickname' => $oauthUser->getNickname(),
                    'raw' => $raw,
                ],
            ];
        } catch (Exception $e) {
            throw new Exception("OAuth authentication failed: {$e->getMessage()}");
        }
    }
}
