<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-activity-logs',
            'view-profile',
            'view-settings',
            'view-dashboard',
            'create-user',
            'edit-user',
            'delete-user',
            'view-user',
            'create-category',
            'edit-category',
            'delete-category',
            'view-category',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
