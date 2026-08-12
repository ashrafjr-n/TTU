<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        $medications = [
            ['name_ar' => 'باراسيتامول 500 ملغ', 'name_en' => 'Paracetamol 500mg', 'stock_quantity' => 200, 'low_stock_threshold' => 30, 'unit' => 'شريط'],
            ['name_ar' => 'إيبوبروفين 400 ملغ', 'name_en' => 'Ibuprofen 400mg', 'stock_quantity' => 150, 'low_stock_threshold' => 20, 'unit' => 'شريط'],
            ['name_ar' => 'أموكسيسيلين 500 ملغ', 'name_en' => 'Amoxicillin 500mg', 'stock_quantity' => 80, 'low_stock_threshold' => 15, 'unit' => 'علبة'],
            ['name_ar' => 'شراب مضاد للسعال', 'name_en' => 'Cough Relief Syrup', 'stock_quantity' => 40, 'low_stock_threshold' => 10, 'unit' => 'عبوة'],
            ['name_ar' => 'مضاد حساسية (لوراتادين)', 'name_en' => 'Antihistamine (Loratadine)', 'stock_quantity' => 60, 'low_stock_threshold' => 10, 'unit' => 'شريط'],
            ['name_ar' => 'محلول ملحي للأنف', 'name_en' => 'Saline Nasal Solution', 'stock_quantity' => 100, 'low_stock_threshold' => 20, 'unit' => 'عبوة'],
            ['name_ar' => 'مرهم مضاد حيوي موضعي', 'name_en' => 'Topical Antibiotic Ointment', 'stock_quantity' => 35, 'low_stock_threshold' => 10, 'unit' => 'أنبوب'],

            // ثلاثة أدوية منزلية شائعة إضافية
            ['name_ar' => 'بنادول (باراسيتامول)', 'name_en' => 'Panadol (Paracetamol)', 'stock_quantity' => 180, 'low_stock_threshold' => 25, 'unit' => 'شريط'],
            ['name_ar' => 'سينوتاب (مضاد للزكام والإنفلونزا)', 'name_en' => 'Sinutab (Cold & Flu Relief)', 'stock_quantity' => 90, 'low_stock_threshold' => 15, 'unit' => 'شريط'],
            ['name_ar' => 'ستربسلز (أقراص لالتهاب الحلق)', 'name_en' => 'Strepsils (Throat Lozenges)', 'stock_quantity' => 100, 'low_stock_threshold' => 20, 'unit' => 'عبوة'],
        ];

        foreach ($medications as $medication) {
            Medication::firstOrCreate(['name_en' => $medication['name_en']], $medication);
        }
    }
}
