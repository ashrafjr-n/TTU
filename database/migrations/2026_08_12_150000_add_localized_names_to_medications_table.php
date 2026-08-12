<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "name" الوحيد يُستبدل بعمودين name_ar/name_en — الاسم المعروض يصير
 * يعتمد على لغة الواجهة الحالية بدل نص ثابت بلغة واحدة (نفس فكرة الأسماء
 * المترجمة بباقي التطبيق). القيم القديمة (عربية دائمًا) تُنسخ لـ name_ar،
 * ولها ترجمة إنجليزية معروفة للأدوية السبعة المزروعة أصلًا عبر MedicationSeeder
 * — أي صف آخر (دواء أضافه مدير يدويًا بلغة واحدة) يحصل على name_en = name_ar
 * كقيمة احتياطية بدل ترك الحقل فارغًا، ريثما يعدّله مدير لاحقًا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('id');
            $table->string('name_en')->nullable()->after('name_ar');
        });

        $knownTranslations = [
            'باراسيتامول 500 ملغ' => 'Paracetamol 500mg',
            'إيبوبروفين 400 ملغ' => 'Ibuprofen 400mg',
            'أموكسيسيلين 500 ملغ' => 'Amoxicillin 500mg',
            'شراب مضاد للسعال' => 'Cough Relief Syrup',
            'مضاد حساسية (لوراتادين)' => 'Antihistamine (Loratadine)',
            'محلول ملحي للأنف' => 'Saline Nasal Solution',
            'مرهم مضاد حيوي موضعي' => 'Topical Antibiotic Ointment',
        ];

        DB::table('medications')->orderBy('id')->get(['id', 'name'])->each(function ($medication) use ($knownTranslations) {
            DB::table('medications')->where('id', $medication->id)->update([
                'name_ar' => $medication->name,
                'name_en' => $knownTranslations[$medication->name] ?? $medication->name,
            ]);
        });

        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::table('medications')->orderBy('id')->get(['id', 'name_ar'])->each(function ($medication) {
            DB::table('medications')->where('id', $medication->id)->update(['name' => $medication->name_ar]);
        });

        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en']);
        });
    }
};
