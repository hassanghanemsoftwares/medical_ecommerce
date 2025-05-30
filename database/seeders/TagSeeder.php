<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'Pain Relief',
            'Vitamins',
            'Supplements',
            'First Aid',
            'Diabetes Care',
            'Skin Care',
            'Cold & Flu',
            'Allergy',
            'Baby Care',
            'Heart Health',
        ];

        foreach ($tags as $tag) {
            Tag::create(['name' => $tag]);
        }
    }
}
