<?php

namespace Database\Seeders;

use App\Models\UniversityRecord;
use Illuminate\Database\Seeder;

class UniversityRecordSeeder extends Seeder
{
    public function run(): void
    {
        // أرقام جامعية وهمية صحيحة (طلاب) — 8 أرقام
        $students = ['20210123', '20210456', '20210789', '20210999', '20210555'];

        foreach ($students as $id) {
            UniversityRecord::create([
                'identifier' => $id,
                'type' => 'student',
                'is_valid' => true,
            ]);
        }

        // أرقام وظيفية وهمية صحيحة (موظفين) — 4 أرقام بالضبط
        $staff = ['2320', '4491', '7758'];

        foreach ($staff as $id) {
            UniversityRecord::create([
                'identifier' => $id,
                'type' => 'staff',
                'is_valid' => true,
            ]);
        }
    }
}