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

            // learning video Permissions
            'view-learning-video',
            'create-learning-video',
            'edit-learning-video',
            'delete-learning-video',

            // ecommerce Permissions
            'view-ecommerce',
            'create-ecommerce',
            'edit-ecommerce',
            'delete-ecommerce',

            // product Permissions
            'view-product',
            'create-product',
            'edit-product',
            'delete-product',

            // stock Permissions
            'view-stock',
            'view-stock-adjustment',
            'create-stock-adjustment',
            'delete-stock-adjustment',

            // Client Permissions
            'view-client',
            'create-client',
            'edit-client',
            'delete-client',
            // coupon Permissions
            'view-coupon',
            'create-coupon',
            'edit-coupon',
            'delete-coupon',
            // order Permissions
            'view-order',
            'create-order',
            'edit-order',
            'delete-order',

            // return-order Permissions
            'view-return-order',
            'create-return-order',
            'edit-return-order',
            'delete-return-order',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
