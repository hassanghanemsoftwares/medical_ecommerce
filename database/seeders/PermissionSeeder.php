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

            // Brand Permissions
            // 'view-brand',
            // 'create-brand',
            // 'edit-brand',
            // 'delete-brand',

            // ColorSeason Permissions
            // 'view-color-season',
            // 'create-color-season',
            // 'edit-color-season',
            // 'delete-color-season',

            // Color Permissions
            // 'view-color',
            // 'create-color',
            // 'edit-color',
            // 'delete-color',

            // Warehouse Permissions
            // 'view-warehouse',
            // 'create-warehouse',
            // 'edit-warehouse',
            // 'delete-warehouse',

            // Shelf Permissions
            // 'view-shelf',
            // 'create-shelf',
            // 'edit-shelf',
            // 'delete-shelf',

            // Size Permissions
            // 'view-size',
            // 'create-size',
            // 'edit-size',
            // 'delete-size',

            // Tag Permissions
            // 'view-tag',
            // 'create-tag',
            // 'edit-tag',
            // 'delete-tag',

            // Configuration Permissions
            // 'view-configuration',
            // 'edit-configuration',

            // learning video Permissions
            'view-learning-video',
            'create-learning-video',
            'edit-learning-video',
            'delete-learning-video',

            // Home Section Permissions
            'view-home-section',
            'create-home-section',
            'edit-home-section',
            'delete-home-section',

            // Home occupation Permissions
            // 'view-occupation',
            // 'create-occupation',
            // 'edit-occupation',
            // 'delete-occupation',

            // product Permissions

            'view-product',
            'create-product',
            'edit-product',
            'delete-product',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
