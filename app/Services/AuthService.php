<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OAuthProvider;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        /** @var User $user */
        $user = User::create([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => Hash::make((string) $data['password']),
            'is_oauth_user' => false,
        ]);

        $user->assignRole($data['role'] ?? 'member');

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function login(array $data): array
    {
        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check((string) $data['password'], (string) $user->password)) {
            abort(401, 'Invalid credentials');
        }

        $user->tokens()->delete();
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @param  array<string, mixed>  $profileData
     * @return array<string, mixed>
     */
    public function loginOrRegisterViaOAuth(
        string $provider,
        string $providerId,
        string $email,
        string $name,
        array $profileData = []
    ): array {
        $oauthProvider = OAuthProvider::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($oauthProvider) {
            /** @var User $user */
            $user = $oauthProvider->user;
            $user->update(['last_login_at' => now()]);
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
                'is_new_account' => false,
                'is_new_provider' => false,
            ];
        }

        /** @var User|null $existingUser */
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            OAuthProvider::create([
                'user_id' => $existingUser->id,
                'provider' => $provider,
                'provider_id' => $providerId,
                'email' => $email,
                'profile_data' => $profileData,
            ]);

            $existingUser->update([
                'last_login_at' => now(),
                'is_oauth_user' => true,
            ]);

            $existingUser->tokens()->delete();
            $token = $existingUser->createToken('auth_token')->plainTextToken;

            return [
                'user' => $existingUser,
                'token' => $token,
                'is_new_account' => false,
                'is_new_provider' => true,
                'message' => 'Account linked successfully',
            ];
        }

        /** @var User $user */
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'is_oauth_user' => true,
            'last_login_at' => now(),
        ]);

        OAuthProvider::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerId,
            'email' => $email,
            'profile_data' => $profileData,
        ]);

        $user->assignRole('member');

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'is_new_account' => true,
            'is_new_provider' => true,
        ];
    }
}
