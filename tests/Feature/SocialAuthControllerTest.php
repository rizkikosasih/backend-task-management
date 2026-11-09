<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OAuthProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Helper to mock Socialite driver.
     */
    protected function mockSocialite(string $provider, array $userData = []): void
    {
        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn($userData['id'] ?? '123456');
        $abstractUser->shouldReceive('getEmail')->andReturn(array_key_exists('email', $userData) ? $userData['email'] : 'mock@example.com');
        $abstractUser->shouldReceive('getName')->andReturn($userData['name'] ?? 'Mock User');
        $abstractUser->shouldReceive('getAvatar')->andReturn($userData['avatar'] ?? 'https://avatar.url');
        $abstractUser->shouldReceive('getNickname')->andReturn($userData['nickname'] ?? 'mockuser');
        $abstractUser->shouldReceive('getRaw')->andReturn($userData['raw'] ?? []);

        $providerMock = Mockery::mock(AbstractProvider::class);
        $providerMock->shouldReceive('stateless')->andReturnSelf();
        $providerMock->shouldReceive('user')->andReturn($abstractUser);

        // Mock redirect response
        $redirectMock = Mockery::mock(RedirectResponse::class);
        $redirectMock->shouldReceive('getTargetUrl')
            ->andReturn("https://accounts.google.com/o/oauth2/v2/auth?client_id=google-id&redirect_uri=http://localhost:8000/api/oauth/{$provider}/callback");
        $providerMock->shouldReceive('redirect')->andReturn($redirectMock);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($providerMock);
    }

    /**
     * Test GET /api/oauth/{provider}/redirect returns the redirect URL.
     */
    public function test_oauth_redirect_returns_url_successfully(): void
    {
        $this->mockSocialite('google');

        $response = $this->getJson(route('oauth.redirect', ['provider' => 'google']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['redirect_url'],
            ])
            ->assertJsonFragment([
                'success' => true,
            ]);
    }

    /**
     * Test GET /api/oauth/{provider}/redirect rejects invalid providers.
     */
    public function test_oauth_redirect_rejects_invalid_provider(): void
    {
        $response = $this->getJson('/api/oauth/facebook/redirect');

        $response->assertStatus(404); // Route constraints restrict provider to google|github, so it will 404
    }

    /**
     * Test callback registers new user with email.
     */
    public function test_oauth_callback_creates_and_logs_in_new_user(): void
    {
        $userData = [
            'id' => 'oauth-goog-123',
            'email' => 'newgoogle@example.com',
            'name' => 'Googler User',
        ];
        $this->mockSocialite('google', $userData);

        $response = $this->getJson(route('oauth.callback', ['provider' => 'google', 'code' => 'mock-code']));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'is_new_account',
                    'is_new_provider',
                ],
            ])
            ->assertJsonFragment([
                'is_new_account' => true,
                'is_new_provider' => true,
            ]);

        $this->assertDatabaseHas('users', ['email' => 'newgoogle@example.com']);
        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'google',
            'provider_id' => 'oauth-goog-123',
            'email' => 'newgoogle@example.com',
        ]);
    }

    /**
     * Test callback links existing email to provider.
     */
    public function test_oauth_callback_links_existing_user_by_email(): void
    {
        $user = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $userData = [
            'id' => 'oauth-goog-456',
            'email' => 'existing@example.com',
            'name' => 'Existing User Extra',
        ];
        $this->mockSocialite('google', $userData);

        $response = $this->getJson(route('oauth.callback', ['provider' => 'google', 'code' => 'mock-code']));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_new_account' => false,
                'is_new_provider' => true,
            ])
            ->assertJsonPath('message', 'Account linked successfully');

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'oauth-goog-456',
        ]);
    }

    /**
     * Test manually linking a provider via POST /api/oauth/{provider}/link.
     */
    public function test_authenticated_user_can_link_provider_manually(): void
    {
        $user = User::create([
            'name' => 'Manually Linked User',
            'email' => 'manual@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $userData = [
            'id' => 'oauth-git-999',
            'email' => 'manual@example.com',
            'name' => 'GitHub Profile Name',
        ];
        $this->mockSocialite('github', $userData);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('oauth.link', ['provider' => 'github']));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'github linked successfully',
            ]);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => 'oauth-git-999',
        ]);
    }

    /**
     * Test manual linking fails if user is already linked to this provider.
     */
    public function test_authenticated_user_cannot_link_same_provider_twice(): void
    {
        $user = User::create([
            'name' => 'Double Link User',
            'email' => 'double@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        OAuthProvider::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-linked-already',
            'email' => 'double@example.com',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('oauth.link', ['provider' => 'google']));

        $response->assertStatus(400)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Account already linked to google',
            ]);
    }

    /**
     * Test manual linking fails if provider id is linked to another user.
     */
    public function test_authenticated_user_cannot_link_provider_id_taken_by_another_user(): void
    {
        $userA = User::create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'password' => bcrypt('password123'),
        ]);
        $userA->assignRole('member');

        OAuthProvider::create([
            'user_id' => $userA->id,
            'provider' => 'github',
            'provider_id' => 'taken-github-id',
            'email' => 'usera@example.com',
        ]);

        $userB = User::create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'password' => bcrypt('password123'),
        ]);
        $userB->assignRole('member');

        $tokenB = $userB->createToken('test_token')->plainTextToken;

        // Mock Socialite returning User A's git ID
        $userData = [
            'id' => 'taken-github-id',
            'email' => 'userb@example.com',
            'name' => 'User B',
        ];
        $this->mockSocialite('github', $userData);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenB,
        ])->postJson(route('oauth.link', ['provider' => 'github']));

        $response->assertStatus(400)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'This account is already linked to another user',
            ]);
    }

    public function test_oauth_callback_browser_request_redirects_to_frontend_callback(): void
    {
        $userData = [
            'id' => 'oauth-browser-123',
            'email' => 'browser@example.com',
            'name' => 'Browser User',
        ];
        $this->mockSocialite('google', $userData);

        $response = $this->get(route('oauth.callback', ['provider' => 'google', 'code' => 'mock-code']));

        $response->assertStatus(302);
        $this->assertStringContainsString('/oauth/callback?token=', (string) $response->headers->get('Location'));
    }

    public function test_oauth_callback_with_missing_email_redirects_browser_to_login_error(): void
    {
        $userData = [
            'id' => 'oauth-no-email-456',
            'email' => null,
            'name' => 'No Email User',
        ];
        $this->mockSocialite('github', $userData);

        $response = $this->get(route('oauth.callback', ['provider' => 'github', 'code' => 'mock-code']));

        $response->assertStatus(302);
        $this->assertStringContainsString('/login?error=oauth_email_missing&provider=github', (string) $response->headers->get('Location'));
    }

    public function test_oauth_callback_with_missing_email_returns_json_error_for_api_client(): void
    {
        $userData = [
            'id' => 'oauth-no-email-789',
            'email' => null,
            'name' => 'API No Email User',
        ];
        $this->mockSocialite('github', $userData);

        $response = $this->getJson(route('oauth.callback', ['provider' => 'github', 'code' => 'mock-code']));

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Alamat email publik tidak tersedia dari penyedia OAuth.',
            ]);
    }
}
