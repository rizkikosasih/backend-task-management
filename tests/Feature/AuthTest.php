<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_register_successfully_as_member_by_default(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('register'), $payload);

        $response->assertStatus(201)->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_user_cannot_register_with_mismatched_password(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'mismatchedpassword',
        ];

        $response = $this->postJson(route('register'), $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_login_successfully(): void
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $payload = [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('login'), $payload);

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $payload = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson(route('login'), $payload);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('logout'));

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    public function test_authenticated_user_can_retrieve_profile_data(): void
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('member');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('me'));

        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'roles',
                'permissions',
            ],
        ]);
    }
}
