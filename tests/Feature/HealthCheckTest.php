<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_root_endpoint_returns_service_status(): void
    {
        $response = $this->getJson('/');

        $response->assertStatus(200)->assertJson([
            'status' => 'online',
            'service' => 'Task Management API',
        ]);
    }
}
