<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $member = Role::firstOrCreate(['name' => 'member']);

        $permissions = [
            'view project',
            'create project',
            'update project',
            'delete project',

            'view task',
            'create task',
            'update task detail',
            'update task status',
            'delete task',

            'view comment',
            'create comment',
            'delete comment',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $admin->syncPermissions(Permission::all());

        $member->syncPermissions([
            'view project',
            'view task',
            'view comment',
            'update task status',
            'create comment',
            'delete comment',
        ]);
    }
}
