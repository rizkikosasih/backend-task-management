<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        RateLimiter::clear('api-auth');
        RateLimiter::clear('api-mutations');
        RateLimiter::clear('api-oauth');
    }

    public function test_auth_rate_limiting_throttles_excessive_attempts(): void
    {
        $payload = [
            'email' => 'rate-limit-test@example.com',
            'password' => 'wrong-password',
        ];

        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson(route('login'), $payload);
            $response->assertStatus(401);
        }

        $throttledResponse = $this->postJson(route('login'), $payload);
        $throttledResponse->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJson([
                'success' => false,
                'message' => 'Batas frekuensi percobaan autentikasi terlampaui. Silakan tunggu beberapa saat.',
            ]);
    }

    public function test_mutation_rate_limiting_throttles_after_threshold(): void
    {
        $user = User::create([
            'name' => 'Rate Limited Admin',
            'email' => 'admin-rl@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('admin');

        $token = $user->createToken('test_token')->plainTextToken;

        for ($i = 0; $i < 60; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'X-Idempotency-Key' => (string) Str::uuid(),
            ])->postJson(route('projects.create'), [
                'name' => 'Project '.$i,
                'description' => 'Description '.$i,
            ]);
            $response->assertStatus(201);
        }

        $throttledResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Idempotency-Key' => (string) Str::uuid(),
        ])->postJson(route('projects.create'), [
            'name' => 'Project Over Limit',
            'description' => 'Description Over Limit',
        ]);

        $throttledResponse->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJson([
                'success' => false,
                'message' => 'Batas frekuensi mutasi data terlampaui. Silakan tunggu beberapa saat.',
            ]);
    }

    public function test_different_users_have_isolated_rate_limit_buckets(): void
    {
        $userA = User::create([
            'name' => 'User Alpha',
            'email' => 'alpha@example.com',
            'password' => bcrypt('password123'),
        ]);
        $userA->assignRole('admin');
        $tokenA = $userA->createToken('token_a')->plainTextToken;

        $userB = User::create([
            'name' => 'User Beta',
            'email' => 'beta@example.com',
            'password' => bcrypt('password123'),
        ]);
        $userB->assignRole('admin');
        $tokenB = $userB->createToken('token_b')->plainTextToken;

        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$tokenA,
                'X-Idempotency-Key' => (string) Str::uuid(),
            ])->postJson(route('projects.create'), [
                'name' => 'Alpha Project '.$i,
            ])->assertStatus(201);
        }

        $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenA,
            'X-Idempotency-Key' => (string) Str::uuid(),
        ])->postJson(route('projects.create'), [
            'name' => 'Alpha Project 61',
        ])->assertStatus(429);

        $responseB = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenB,
            'X-Idempotency-Key' => (string) Str::uuid(),
        ])->postJson(route('projects.create'), [
            'name' => 'Beta Project 1',
        ]);

        $responseB->assertStatus(201);
    }
}
