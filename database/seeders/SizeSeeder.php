<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Size;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = [
            ['en' => 'Small', 'ar' => 'صغير'],
            ['en' => 'Medium', 'ar' => 'متوسط'],
            ['en' => 'Large', 'ar' => 'كبير'],
            ['en' => 'Extra Large', 'ar' => 'كبير جداً'],
        ];

        foreach ($sizes as $name) {
            Size::create([
                'name' => $name
            ]);
        }
    }
}
