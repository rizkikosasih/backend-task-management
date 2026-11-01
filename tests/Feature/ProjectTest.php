<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_list_projects(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $token = $admin->createToken('test_token')->plainTextToken;

        Project::create([
            'name' => 'Project A',
            'description' => 'First project',
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('projects.index'));

        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'description', 'created_by'],
            ],
        ]);
    }

    public function test_admin_can_view_any_project_details(): void
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
            'description' => 'Admin 1 project',
            'created_by' => $admin1->id,
        ]);

        $token = $admin2->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('projects.detail', $project->id));

        $response->assertStatus(200)->assertJsonPath('data.name', 'Project A');
    }

    public function test_member_can_view_project_only_if_involved(): void
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
            'name' => 'Involved Project',
            'description' => 'Project with tasks',
            'created_by' => $admin->id,
        ]);

        $otherProject = Project::create([
            'name' => 'Uninvolved Project',
            'description' => 'Project without tasks for member',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'project_id' => $project->id,
            'title' => 'Task One',
            'description' => 'Do something',
            'assigned_to' => $member->id,
            'status' => 'todo',
        ]);

        $token = $member->createToken('test_token')->plainTextToken;

        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('projects.detail', $project->id));

        $response1->assertStatus(200);

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson(route('projects.detail', $otherProject->id));

        $response2->assertStatus(403);
    }

    public function test_admin_can_create_project(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');

        $payload = [
            'name' => 'New Project',
            'description' => 'Project description',
        ];

        $tokenAdmin = $admin->createToken('admin_token')->plainTextToken;
        $responseAdmin = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenAdmin,
        ])->postJson(route('projects.create'), $payload);

        $responseAdmin->assertStatus(201);
    }

    public function test_member_cannot_create_project(): void
    {
        $member = User::create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
        ]);
        $member->assignRole('member');

        $payload = [
            'name' => 'New Project',
            'description' => 'Project description',
        ];

        $tokenMember = $member->createToken('member_token')->plainTextToken;
        $responseMember = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenMember,
        ])->postJson(route('projects.create'), $payload);

        $responseMember->assertStatus(403);
    }

    public function test_project_creator_can_update_project(): void
    {
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin1->assignRole('admin');

        $project = Project::create([
            'name' => 'Project X',
            'description' => 'Original description',
            'created_by' => $admin1->id,
        ]);

        $payload = [
            'name' => 'Project X Updated',
            'description' => 'New description',
        ];

        $token1 = $admin1->createToken('token1')->plainTextToken;
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->putJson(route('projects.update', $project->id), $payload);

        $response1->assertStatus(200);
    }

    public function test_non_project_creator_cannot_update_project(): void
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
            'name' => 'Project X',
            'description' => 'Original description',
            'created_by' => $admin1->id,
        ]);

        $payload = [
            'name' => 'Project X Updated',
            'description' => 'New description',
        ];

        $token2 = $admin2->createToken('token2')->plainTextToken;
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->putJson(route('projects.update', $project->id), $payload);

        $response2->assertStatus(403);
    }

    public function test_non_project_creator_cannot_delete_project(): void
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
            'name' => 'Project Y',
            'description' => 'Description',
            'created_by' => $admin1->id,
        ]);

        $token2 = $admin2->createToken('token2')->plainTextToken;
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->deleteJson(route('projects.delete', $project->id));

        $response2->assertStatus(403);
    }

    public function test_project_creator_can_delete_project(): void
    {
        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin1->assignRole('admin');

        $project = Project::create([
            'name' => 'Project Y',
            'description' => 'Description',
            'created_by' => $admin1->id,
        ]);

        $token1 = $admin1->createToken('token1')->plainTextToken;
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->deleteJson(route('projects.delete', $project->id));

        $response1->assertStatus(200);
    }
}
