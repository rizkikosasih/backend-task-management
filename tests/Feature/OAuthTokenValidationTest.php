<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OAuthProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthTokenValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['services.google.client_id' => 'mock-google-client-id']);
    }

    public function test_authenticated_user_can_link_valid_google_token(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-user-999',
                'email' => 'user.verified@gmail.com',
                'email_verified' => 'true',
                'name' => 'Verified Google User',
                'picture' => 'https://lh3.googleusercontent.com/avatar',
                'aud' => 'mock-google-client-id',
            ], 200),
        ]);

        $user = User::create([
            'name' => 'Local User',
            'email' => 'local@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('oauth.link', ['provider' => 'google']), [
            'token' => 'header.payload.signature',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'google linked successfully',
            ]);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-user-999',
            'email' => 'user.verified@gmail.com',
        ]);
    }

    public function test_linking_fails_when_google_token_is_invalid(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'error' => 'invalid_token',
                'error_description' => 'Invalid Value',
            ], 400),
        ]);

        $user = User::create([
            'name' => 'Local User',
            'email' => 'local2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('oauth.link', ['provider' => 'google']), [
            'token' => 'invalid.jwt.token',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Token OAuth Google tidak valid atau telah kedaluwarsa.',
            ]);
    }

    public function test_linking_fails_when_google_email_is_unverified(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-unverified-1',
                'email' => 'unverified@gmail.com',
                'email_verified' => 'false',
                'aud' => 'mock-google-client-id',
            ], 200),
        ]);

        $user = User::create([
            'name' => 'Local User',
            'email' => 'local3@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('oauth.link', ['provider' => 'google']), [
            'token' => 'unverified.jwt.token',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Alamat email akun Google belum terverifikasi.',
            ]);
    }

    public function test_authenticated_user_can_link_valid_github_token(): void
    {
        Http::fake([
            'https://api.github.com/user' => Http::response([
                'id' => 778899,
                'login' => 'octotester',
                'name' => 'Octo Tester',
                'email' => 'octo@github.com',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/778899',
            ], 200),
        ]);

        $user = User::create([
            'name' => 'Local GitHub User',
            'email' => 'localgit@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('oauth.link', ['provider' => 'github']), [
            'token' => 'mock_github_access_token_123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'github linked successfully',
            ]);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '778899',
            'email' => 'octo@github.com',
        ]);
    }

    public function test_linking_fails_when_github_token_is_revoked(): void
    {
        Http::fake([
            'https://api.github.com/user' => Http::response([
                'message' => 'Bad credentials',
            ], 401),
        ]);

        $user = User::create([
            'name' => 'Local User',
            'email' => 'localfail@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('oauth.link', ['provider' => 'github']), [
            'token' => 'mock_revoked_token',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Token OAuth GitHub tidak valid atau telah dicabut.',
            ]);
    }

    public function test_linking_fails_when_provider_id_already_taken_by_another_user(): void
    {
        Http::fake([
            'https://api.github.com/user' => Http::response([
                'id' => 555666,
                'login' => 'shareduser',
                'email' => 'shared@example.com',
            ], 200),
        ]);

        $userA = User::create([
            'name' => 'User Alpha',
            'email' => 'alpha.user@example.com',
            'password' => bcrypt('password123'),
        ]);
        $userA->assignRole('member');

        OAuthProvider::create([
            'user_id' => $userA->id,
            'provider' => 'github',
            'provider_id' => '555666',
            'email' => 'shared@example.com',
        ]);

        $userB = User::create([
            'name' => 'User Beta',
            'email' => 'beta.user@example.com',
            'password' => bcrypt('password123'),
        ]);
        $userB->assignRole('member');

        $tokenB = $userB->createToken('token_b')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenB,
        ])->postJson(route('oauth.link', ['provider' => 'github']), [
            'token' => 'mock_token_555666',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This account is already linked to another user',
            ]);
    }
}
