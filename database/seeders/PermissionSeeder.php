<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-activity_logs',

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

            // product Permissions
            'view-product',
            'create-product',
            'edit-product',
            'delete-product',

            // stock Permissions
            'view-stock',
            'view-stock_adjustment',
            'create-stock_adjustment',

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

            // return_order Permissions
            'view-return_order',
            'create-return_order',
            'edit-return_order',
            'delete-return_order',

            // pre_order Permissions
            'view-pre_order',
            'create-pre_order',
            'edit-pre_order',
            'delete-pre_order',

            // learning video Permissions
            'view-learning_video',
            'create-learning_video',
            'edit-learning_video',
            'delete-learning_video',

            // home_section Permissions
            'view-home_section',
            'create-home_section',
            'edit-home_section',
            'delete-home_section',

            // contacts Permissions
            'view-contacts',

            // subscription_plan Permissions
            'view-subscription_plan',
            'create-subscription_plan',
            'edit-subscription_plan',
            'delete-subscription_plan',

            // subscription Permissions
            'view-subscription',
            'create-subscription',
            'edit-subscription',
            'delete-subscription',

            // team_member Permissions
            'view-team_member',
            'create-team_member',
            'edit-team_member',
            'delete-team_member',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
