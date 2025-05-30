<?php

namespace Database\Seeders;

use App\Models\Shelf;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ShelfSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouse = Warehouse::where('name', 'Main Warehouse')
            ->where('location', 'Tyre, Lebanon')
            ->first();

        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'name' => 'Main Warehouse',
                'location' => 'Tyre, Lebanon',
            ]);
        }

        $shelves = [
            ['name' => 'Shelf A1', 'location' => 'First Floor'],
            ['name' => 'Shelf B1', 'location' => 'Second Floor'],
            ['name' => 'Cold Storage', 'location' => 'Basement'],
            ['name' => 'Bulk Area', 'location' => 'Ground Floor'],
        ];

        foreach ($shelves as $shelf) {
            Shelf::create([
                'warehouse_id' => $warehouse->id,
                'name' => $shelf['name'],
                'location' => $shelf['location'],
            ]);
        }
    }
}
