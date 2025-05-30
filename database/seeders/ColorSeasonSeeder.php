<?php

namespace Database\Seeders;

use App\Models\ColorSeason;
use Illuminate\Database\Seeder;

class ColorSeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seasons = [
            ['en' => 'Spring', 'ar' => 'الربيع'],
            ['en' => 'Summer', 'ar' => 'الصيف'],
            ['en' => 'Autumn', 'ar' => 'الخريف'],
            ['en' => 'Winter', 'ar' => 'الشتاء'],
            ['en' => 'All Seasons', 'ar' => 'كل المواسم'],
        ];

        foreach ($seasons as $season) {
            ColorSeason::create([
                'name' => $season,
            ]);
        }
    }
}
