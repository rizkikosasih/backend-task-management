<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OAuthTokenValidatorService
{
    /**
     * @return array{
     *     provider: string,
     *     provider_id: string,
     *     email: string,
     *     name: string|null,
     *     profile_data: array<string, mixed>
     * }
     */
    public function validateToken(string $provider, string $token): array
    {
        return match ($provider) {
            'google' => $this->validateGoogleToken($token),
            'github' => $this->validateGitHubToken($token),
            default => throw new BusinessException(
                'Penyedia OAuth tidak didukung.',
                ['provider' => ['Penyedia OAuth harus google atau github.']],
                400,
            ),
        };
    }

    /**
     * @return array{
     *     provider: string,
     *     provider_id: string,
     *     email: string,
     *     name: string|null,
     *     profile_data: array<string, mixed>
     * }
     */
    protected function validateGoogleToken(string $token): array
    {
        $isJwt = substr_count($token, '.') === 2;
        $endpoint = $isJwt
            ? 'https://oauth2.googleapis.com/tokeninfo?id_token='.urlencode($token)
            : 'https://www.googleapis.com/oauth2/v3/tokeninfo?access_token='.urlencode($token);

        try {
            $response = Http::timeout(5)->get($endpoint);
        } catch (Throwable $e) {
            throw new BusinessException(
                'Gagal menghubungi server verifikasi Google: '.$e->getMessage(),
                ['token' => ['Koneksi verifikasi token Google gagal.']],
                502,
            );
        }

        if (!$response->successful()) {
            throw new BusinessException(
                'Token OAuth Google tidak valid atau telah kedaluwarsa.',
                ['token' => ['Verifikasi token Google gagal.']],
                422,
            );
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$emailVerified) {
            throw new BusinessException(
                'Alamat email akun Google belum terverifikasi.',
                ['email' => ['Email Google wajib berstatus terverifikasi.']],
                422,
            );
        }

        $configuredClientId = (string) config('services.google.client_id');
        $tokenAud = (string) ($payload['aud'] ?? '');
        if ($configuredClientId !== '' && $tokenAud !== '' && $tokenAud !== $configuredClientId) {
            throw new BusinessException(
                'Target audience token Google tidak valid.',
                ['token' => ['Client ID token Google tidak cocok.']],
                422,
            );
        }

        $providerId = (string) ($payload['sub'] ?? '');
        $email = (string) ($payload['email'] ?? '');

        if ($providerId === '' || $email === '') {
            throw new BusinessException(
                'Payload identitas Google tidak lengkap.',
                ['token' => ['Atribut sub atau email tidak ditemukan pada token Google.']],
                422,
            );
        }

        return [
            'provider' => 'google',
            'provider_id' => $providerId,
            'email' => $email,
            'name' => isset($payload['name']) ? (string) $payload['name'] : null,
            'profile_data' => [
                'avatar' => $payload['picture'] ?? null,
                'email_verified' => true,
                'raw' => $payload,
            ],
        ];
    }

    /**
     * @return array{
     *     provider: string,
     *     provider_id: string,
     *     email: string,
     *     name: string|null,
     *     profile_data: array<string, mixed>
     * }
     */
    protected function validateGitHubToken(string $token): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'User-Agent' => 'Task-Management-API',
                    'Accept' => 'application/vnd.github+json',
                ])
                ->get('https://api.github.com/user');
        } catch (Throwable $e) {
            throw new BusinessException(
                'Gagal menghubungi server verifikasi GitHub: '.$e->getMessage(),
                ['token' => ['Koneksi verifikasi token GitHub gagal.']],
                502,
            );
        }

        if (!$response->successful()) {
            throw new BusinessException(
                'Token OAuth GitHub tidak valid atau telah dicabut.',
                ['token' => ['Verifikasi token GitHub gagal.']],
                422,
            );
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $providerId = isset($payload['id']) ? (string) $payload['id'] : '';
        $email = isset($payload['email']) ? (string) $payload['email'] : '';

        if ($email === '') {
            try {
                $emailsResponse = Http::timeout(5)
                    ->withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'User-Agent' => 'Task-Management-API',
                        'Accept' => 'application/vnd.github+json',
                    ])
                    ->get('https://api.github.com/user/emails');

                if ($emailsResponse->successful()) {
                    /** @var mixed $emails */
                    $emails = $emailsResponse->json();
                    if (is_array($emails)) {
                        foreach ($emails as $item) {
                            if (is_array($item) && !empty($item['primary']) && !empty($item['verified']) && !empty($item['email'])) {
                                $email = (string) $item['email'];
                                break;
                            }
                        }
                    }
                }
            } catch (Throwable) {
            }
        }

        if ($providerId === '' || $email === '') {
            throw new BusinessException(
                'Alamat email publik atau terverifikasi tidak ditemukan pada akun GitHub.',
                ['email' => ['Akun GitHub harus memiliki email yang terverifikasi.']],
                422,
            );
        }

        $name = isset($payload['name']) && is_string($payload['name'])
            ? $payload['name']
            : (isset($payload['login']) ? (string) $payload['login'] : null);

        return [
            'provider' => 'github',
            'provider_id' => $providerId,
            'email' => $email,
            'name' => $name,
            'profile_data' => [
                'avatar' => $payload['avatar_url'] ?? null,
                'nickname' => $payload['login'] ?? null,
                'raw' => $payload,
            ],
        ];
    }
}
