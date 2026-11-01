<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_involved_member_can_retrieve_comments(): void
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

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        Comment::create([
            'task_id' => $task->id,
            'user_id' => $member1->id,
            'message' => 'Hello world',
        ]);

        $tokenMember1 = $member1->createToken('token1')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenMember1,
        ])->getJson(route('comments.index', $task->id));

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_uninvolved_member_cannot_retrieve_comments(): void
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

        // task is assigned to member1
        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        Comment::create([
            'task_id' => $task->id,
            'user_id' => $member1->id,
            'message' => 'Hello world',
        ]);

        $tokenMember2 = $member2->createToken('token2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenMember2,
        ])->getJson(route('comments.index', $task->id));

        $response->assertStatus(403);
    }

    public function test_involved_member_can_create_comment(): void
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

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        $tokenMember1 = $member1->createToken('token1')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenMember1,
        ])->postJson(route('comments.store', $task->id), ['message' => 'My comment']);

        $response->assertStatus(201);
    }

    public function test_uninvolved_member_cannot_create_comment(): void
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

        $tokenMember2 = $member2->createToken('token2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenMember2,
        ])->postJson(route('comments.store', $task->id), ['message' => 'My comment']);

        $response->assertStatus(403);
    }

    public function test_comment_owner_can_delete_comment(): void
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

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $member1->id,
            'message' => 'Hello world',
        ]);

        $tokenMember1 = $member1->createToken('token1')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenMember1,
        ])->deleteJson(route('comments.destroy', $comment->id));

        $response->assertStatus(200);
    }

    public function test_non_comment_owner_cannot_delete_comment(): void
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

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Task',
            'assigned_to' => $member1->id,
            'status' => 'todo',
        ]);

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $member1->id,
            'message' => 'Hello world',
        ]);

        $tokenAdmin = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenAdmin,
        ])->deleteJson(route('comments.destroy', $comment->id));

        $response->assertStatus(403);
    }
}
