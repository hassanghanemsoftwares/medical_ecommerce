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
            ['key' => 'delivery_charge', 'value' => '5.00'],
            ['key' => 'min_stock_alert', 'value' => '5'],
            ['key' => 'store_name', 'value' => 'jays'],
            ['key' => 'contact_email', 'value' => 'support@myawesomestore.com'],
            ['key' => 'contact_phone', 'value' => '+1-555-1234'],
            ['key' => 'store_address', 'value' => 'test'],
            [
                'key' => 'about_us',
                'value' => [
                    'title' => [
                        'en' => 'About Us',
                        'ar' => 'من نحن',
                    ],
                    'description' => [
                        'en' => 'We are an innovative e-commerce platform offering quality products.',
                        'ar' => 'نحن منصة تجارة إلكترونية مبتكرة تقدم منتجات عالية الجودة.',
                    ],
                    'image' => '',
                ],
            ],
        ];

        foreach ($configurations as $configuration) {
            Configuration::firstOrCreate(
                ['key' => $configuration['key']],
                ['value' => $configuration['value']]
            );
        }
    }
}
