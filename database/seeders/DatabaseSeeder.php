<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TeamSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            ConfigurationSeeder::class,
            SizeSeeder::class,
            ColorSeasonSeeder::class,
            ColorSeeder::class,
            TagSeeder::class,
            OccupationSeeder::class,
            WarehouseSeeder::class,
            ShelfSeeder::class,
        ]);
    }
}
