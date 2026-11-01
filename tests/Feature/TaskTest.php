<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_only_sees_assigned_tasks_in_list(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $member1 = User::create([
            'name' => 'Member One',
            'email' => 'member1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $member1->assignRole('member');

        $member2 = User::create([
            'name' => 'Member Two',
            'email' => 'member2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $member2->assignRole('member');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'project_id' => $project->id,
            'title' => 'Task for Member One',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        Task::create([
            'project_id' => $project->id,
            'title' => 'Task for Member Two',
            'assigned_to' => $member2->id,
            'status' => 'todo',
        ]);

        $token1 = $member1->createToken('token1')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token1,
        ])->getJson(route('tasks.index'));

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_admin_sees_all_tasks_in_list(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $member1 = User::create([
            'name' => 'Member One',
            'email' => 'member1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $member1->assignRole('member');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'project_id' => $project->id,
            'title' => 'Task for Member One',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        $tokenAdmin = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenAdmin,
        ])->getJson(route('tasks.index'));

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_project_owner_can_create_tasks(): void
    {
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin1->assignRole('admin');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin1->id,
        ]);

        $payload = [
            'project_id' => $project->id,
            'title' => 'New Task',
            'description' => 'Description',
        ];

        $token1 = $admin1->createToken('token1')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token1,
        ])->postJson(route('tasks.create'), $payload);

        $response->assertStatus(201);
    }

    public function test_non_project_owner_cannot_create_tasks(): void
    {
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin1->assignRole('admin');

        $admin2 = User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin2->assignRole('admin');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin1->id,
        ]);

        $payload = [
            'project_id' => $project->id,
            'title' => 'New Task',
            'description' => 'Description',
        ];

        $token2 = $admin2->createToken('token2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2,
        ])->postJson(route('tasks.create'), $payload);

        $response->assertStatus(403);
    }

    public function test_project_owner_can_update_task_details(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Old Title',
            'status' => 'todo',
        ]);

        $payload = [
            'title' => 'Updated Title',
        ];

        $token = $admin->createToken('token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson(route('tasks.update', $task->id), $payload);

        $response->assertStatus(200);
    }

    public function test_non_project_owner_cannot_update_task_details(): void
    {
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin1->assignRole('admin');

        $admin2 = User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin2->assignRole('admin');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin1->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Old Title',
            'status' => 'todo',
        ]);

        $payload = [
            'title' => 'Updated Title',
        ];

        $token2 = $admin2->createToken('token2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2,
        ])->putJson(route('tasks.update', $task->id), $payload);

        $response->assertStatus(403);
    }

    public function test_task_assignee_can_update_status(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $member = User::create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
        ]);
        $member->assignRole('member');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'assigned_to' => $member->id,
            'status' => 'todo',
        ]);

        $tokenMember = $member->createToken('token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenMember,
        ])->patchJson(route('tasks.status', $task->id), ['status' => 'in_progress']);

        $response->assertStatus(200);
    }

    public function test_non_assignee_cannot_update_status(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $member1 = User::create([
            'name' => 'Member One',
            'email' => 'member1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $member1->assignRole('member');

        $member2 = User::create([
            'name' => 'Member Two',
            'email' => 'member2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $member2->assignRole('member');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        $tokenMember2 = $member2->createToken('token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenMember2,
        ])->patchJson(route('tasks.status', $task->id), ['status' => 'in_progress']);

        $response->assertStatus(403);
    }

    public function test_done_task_cannot_be_reverted(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'status' => 'done',
        ]);

        $token = $admin->createToken('token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->patchJson(route('tasks.status', $task->id), ['status' => 'in_progress']);

        $response->assertStatus(422);
    }

    public function test_project_owner_can_delete_task(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'status' => 'todo',
        ]);

        $token = $admin->createToken('token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson(route('tasks.delete', $task->id));

        $response->assertStatus(200);
    }

    public function test_non_project_owner_cannot_delete_task(): void
    {
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin1->assignRole('admin');

        $admin2 = User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin2->assignRole('admin');

        $project = Project::create([
            'name' => 'Project A',
            'created_by' => $admin1->id,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'status' => 'todo',
        ]);

        $token2 = $admin2->createToken('token2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2,
        ])->deleteJson(route('tasks.delete', $task->id));

        $response->assertStatus(403);
    }
}
