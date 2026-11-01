<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\OAuthProvider;
use App\Models\User;
use App\Services\AuthService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->authService = new AuthService;
    }

    /**
     * Test case 1: Email and provider are completely new.
     * Should create a new user, assign 'member' role, create oauth_providers record, and return token.
     */
    public function test_login_or_register_via_oauth_creates_new_user(): void
    {
        $provider = 'google';
        $providerId = '123456789';
        $email = 'newuser@example.com';
        $name = 'New OAuth User';
        $profileData = ['avatar' => 'https://example.com/avatar.jpg'];

        $result = $this->authService->loginOrRegisterViaOAuth(
            $provider,
            $providerId,
            $email,
            $name,
            $profileData
        );

        $this->assertTrue($result['is_new_account']);
        $this->assertTrue($result['is_new_provider']);
        $this->assertNotEmpty($result['token']);

        $user = $result['user'];
        $this->assertEquals($email, $user->email);
        $this->assertEquals($name, $user->name);
        $this->assertTrue($user->is_oauth_user);
        $this->assertTrue($user->hasRole('member'));

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerId,
            'email' => $email,
        ]);
    }

    /**
     * Test case 2: Email already exists (registered via email/password), but provider is not connected.
     * Should link the provider to the existing user, set is_oauth_user to true, and return token.
     */
    public function test_login_or_register_via_oauth_links_provider_to_existing_user(): void
    {
        // Register user via password first
        $existingUser = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('password123'),
            'is_oauth_user' => false,
        ]);
        $existingUser->assignRole('member');

        $provider = 'google';
        $providerId = '987654321';
        $profileData = ['avatar' => 'https://example.com/existing_avatar.jpg'];

        $result = $this->authService->loginOrRegisterViaOAuth(
            $provider,
            $providerId,
            'existing@example.com',
            'Existing User',
            $profileData
        );

        $this->assertFalse($result['is_new_account']);
        $this->assertTrue($result['is_new_provider']);
        $this->assertEquals('Account linked successfully', $result['message']);
        $this->assertNotEmpty($result['token']);

        $user = $result['user'];
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertTrue($user->is_oauth_user);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerId,
            'email' => 'existing@example.com',
        ]);
    }

    /**
     * Test case 3: User already linked their OAuth provider.
     * Should log in directly without registering or linking new records.
     */
    public function test_login_or_register_via_oauth_logs_in_existing_linked_user(): void
    {
        $user = User::create([
            'name' => 'Linked User',
            'email' => 'linked@example.com',
            'password' => bcrypt('password123'),
            'is_oauth_user' => true,
        ]);
        $user->assignRole('member');

        OAuthProvider::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '11223344',
            'email' => 'linked@example.com',
        ]);

        $result = $this->authService->loginOrRegisterViaOAuth(
            'github',
            '11223344',
            'linked@example.com',
            'Linked User'
        );

        $this->assertFalse($result['is_new_account']);
        $this->assertFalse($result['is_new_provider']);
        $this->assertNotEmpty($result['token']);
        $this->assertEquals($user->id, $result['user']->id);
    }
}
