<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_done_task_status_cannot_be_reverted(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $project = Project::create([
            'name' => 'Project Alpha',
            'created_by' => $admin->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Finished Task',
            'status' => TaskStatus::DONE,
            'assigned_to' => $admin->id,
        ]);

        $token = $admin->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->patchJson(route('tasks.status', $task->id), [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid Task Transition',
            ]);
    }
}
