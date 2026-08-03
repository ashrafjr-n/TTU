<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        $medications = [
            ['name' => 'باراسيتامول 500 ملغ', 'stock_quantity' => 200, 'low_stock_threshold' => 30, 'unit' => 'شريط'],
            ['name' => 'إيبوبروفين 400 ملغ', 'stock_quantity' => 150, 'low_stock_threshold' => 20, 'unit' => 'شريط'],
            ['name' => 'أموكسيسيلين 500 ملغ', 'stock_quantity' => 80, 'low_stock_threshold' => 15, 'unit' => 'علبة'],
            ['name' => 'شراب مضاد للسعال', 'stock_quantity' => 40, 'low_stock_threshold' => 10, 'unit' => 'عبوة'],
            ['name' => 'مضاد حساسية (لوراتادين)', 'stock_quantity' => 60, 'low_stock_threshold' => 10, 'unit' => 'شريط'],
            ['name' => 'محلول ملحي للأنف', 'stock_quantity' => 100, 'low_stock_threshold' => 20, 'unit' => 'عبوة'],
            ['name' => 'مرهم مضاد حيوي موضعي', 'stock_quantity' => 35, 'low_stock_threshold' => 10, 'unit' => 'أنبوب'],
        ];

        foreach ($medications as $medication) {
            Medication::firstOrCreate(['name' => $medication['name']], $medication);
        }
    }
}
