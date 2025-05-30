<?php

namespace Database\Seeders;

use App\Models\Occupation;
use Illuminate\Database\Seeder;

class OccupationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $occupations = [
            ['en' => 'Doctor', 'ar' => 'طبيب'],
            ['en' => 'Pharmacist', 'ar' => 'صيدلي'],
            ['en' => 'Nurse', 'ar' => 'ممرضة'],
            ['en' => 'Medical Student', 'ar' => 'طالب طب'],
            ['en' => 'Dentist', 'ar' => 'طبيب أسنان'],
            ['en' => 'Lab Technician', 'ar' => 'فني مختبر'],
            ['en' => 'Surgeon', 'ar' => 'جراح'],
            ['en' => 'Dermatologist', 'ar' => 'طبيب جلدية'],
            ['en' => 'Pediatrician', 'ar' => 'طبيب أطفال'],
            ['en' => 'General Practitioner', 'ar' => 'طبيب عام'],
        ];

        foreach ($occupations as $occupation) {
            Occupation::create([
                'name' => $occupation,
            ]);
        }
    }
}
