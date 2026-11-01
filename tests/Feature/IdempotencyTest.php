<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_post_project_is_idempotent_with_same_key(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $token = $admin->createToken('test_token')->plainTextToken;
        $idempotencyKey = 'unique-key-12345';

        $payload = [
            'name' => 'Idempotent Project',
            'description' => 'Testing Idempotency',
        ];

        $firstResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson(route('projects.create'), $payload);

        $firstResponse->assertStatus(201);
        $firstId = $firstResponse->json('data.id');

        $secondResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson(route('projects.create'), $payload);

        $secondResponse->assertStatus(201);
        $secondResponse->assertHeader('X-Cache', 'HIT-IDEMPOTENT');
        $this->assertSame($firstId, $secondResponse->json('data.id'));

        $this->assertDatabaseCount('projects', 1);
    }
}
