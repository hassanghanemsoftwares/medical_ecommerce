<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Team;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $teams = Team::all();
        $permissions = Permission::pluck('name')->toArray();

        $admin = User::create([
            'name' => 'Developer Admin',
            'email' => 'hassanghanemsoftwares@gmail.com',
            'password' => bcrypt('Hassan@123'),
        ]);

        $employee = User::create([
            'name' => 'Developer Employee',
            'email' => 'hassanghanemtrade@gmail.com',
            'password' => bcrypt('Hassan@123'),
        ]);

        foreach ($teams as $team) {
            // Set context to this team for Spatie's team-aware functionality
            setPermissionsTeamId($team->id);

            $adminRole = Role::where('name', 'admin')->where('team_id', $team->id)->first();
            $employeeRole = Role::where('name', 'employee')->where('team_id', $team->id)->first();

            $adminRole->givePermissionTo($permissions);
            $employeeRole->givePermissionTo(['view-profile', 'view-settings', 'view-category']);

            $admin->assignRole($adminRole);
            $employee->assignRole($employeeRole);
        }
    }
}
