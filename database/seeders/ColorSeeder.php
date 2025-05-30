<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Models\ColorSeason;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => ['en' => 'Red',       'ar' => 'أحمر'],      'code' => '#FF0000', 'season' => 'Winter'],
            ['name' => ['en' => 'Green',     'ar' => 'أخضر'],      'code' => '#00FF00', 'season' => 'Spring'],
            ['name' => ['en' => 'Blue',      'ar' => 'أزرق'],      'code' => '#0000FF', 'season' => 'Summer'],
            ['name' => ['en' => 'Orange',    'ar' => 'برتقالي'],    'code' => '#FFA500', 'season' => 'Autumn'],
            ['name' => ['en' => 'Black',     'ar' => 'أسود'],      'code' => '#000000', 'season' => 'All Seasons'],
            ['name' => ['en' => 'White',     'ar' => 'أبيض'],      'code' => '#FFFFFF', 'season' => 'All Seasons'],
            ['name' => ['en' => 'Purple',    'ar' => 'أرجواني'],   'code' => '#800080', 'season' => 'Winter'],
            ['name' => ['en' => 'Yellow',    'ar' => 'أصفر'],      'code' => '#FFFF00', 'season' => 'Spring'],
            ['name' => ['en' => 'Brown',     'ar' => 'بني'],       'code' => '#A52A2A', 'season' => 'Autumn'],
            ['name' => ['en' => 'Pink',      'ar' => 'زهري'],      'code' => '#FFC0CB', 'season' => 'Summer'],
            ['name' => ['en' => 'Gray',      'ar' => 'رمادي'],     'code' => '#808080', 'season' => 'All Seasons'],
            ['name' => ['en' => 'Cyan',      'ar' => 'سماوي'],     'code' => '#00FFFF', 'season' => 'Summer'],
            ['name' => ['en' => 'Magenta',   'ar' => 'أرجواني فاتح'], 'code' => '#FF00FF', 'season' => 'Spring'],
            ['name' => ['en' => 'Lime',      'ar' => 'ليموني'],    'code' => '#00FF00', 'season' => 'Spring'],
            ['name' => ['en' => 'Navy',      'ar' => 'كحلي'],      'code' => '#000080', 'season' => 'Winter'],
            ['name' => ['en' => 'Olive',     'ar' => 'زيتي'],      'code' => '#808000', 'season' => 'Autumn'],
            ['name' => ['en' => 'Teal',      'ar' => 'أخضر مزرق'], 'code' => '#008080', 'season' => 'Summer'],
            ['name' => ['en' => 'Maroon',    'ar' => 'كستنائي'],   'code' => '#800000', 'season' => 'Winter'],
            ['name' => ['en' => 'Silver',    'ar' => 'فضي'],       'code' => '#C0C0C0', 'season' => 'All Seasons'],
            ['name' => ['en' => 'Gold',      'ar' => 'ذهبي'],      'code' => '#FFD700', 'season' => 'Autumn'],
            ['name' => ['en' => 'Coral',     'ar' => 'مرجاني'],    'code' => '#FF7F50', 'season' => 'Summer'],
            ['name' => ['en' => 'Turquoise', 'ar' => 'فيروزي'],    'code' => '#40E0D0', 'season' => 'Spring'],
            ['name' => ['en' => 'Beige',     'ar' => 'بيج'],       'code' => '#F5F5DC', 'season' => 'All Seasons'],
            ['name' => ['en' => 'Indigo',    'ar' => 'نيلي'],      'code' => '#4B0082', 'season' => 'Winter'],
        ];


        foreach ($colors as $color) {
            // Query ColorSeason to get ID by JSON key 'name->en'
            $season = ColorSeason::where('name->en', $color['season'])->first();

            if (!$season) {
                throw new \Exception("ColorSeason with name '{$color['season']}' not found.");
            }

            Color::create([
                'name' => $color['name'],
                'code' => $color['code'],
                'color_season_id' => $season->id,
            ]);
        }
    }
}
