<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UniversityRecordSeeder::class,
            UserSeeder::class,
            MedicationSeeder::class,
        ]);
    }
}
