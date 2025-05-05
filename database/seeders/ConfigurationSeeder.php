<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            ['key' => 'theme_color1', 'value' => '#324057'],
            ['key' => 'theme_color2', 'value' => '#EEABAD'],
            ['key' => 'theme_color3', 'value' => '#EDCFCA'],
            ['key' => 'theme_color4', 'value' => '#A1B6D8'],
            ['key' => 'delivery_charge', 'value' => '5.00'],
            ['key' => 'min_stock_alert', 'value' => '10'],
            ['key' => 'store_name', 'value' => 'jays'],
            ['key' => 'contact_email', 'value' => 'support@myawesomestore.com'],
            ['key' => 'contact_phone', 'value' => '+1-555-1234'],
            ['key' => 'store_address', 'value' => 'test'],
        ];

        foreach ($configurations as $configuration) {
            Configuration::create($configuration);
        }
    }
}
