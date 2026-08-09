<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "سجلات الجامعة" فقدت آخر مستهلك فعلي لها بعد إلغاء التسجيل الذاتي — لم
 * يعد شيء بالتطبيق يقرأ منها ليتحقق من صحة رقم، وبقيت مجرد بيانات إدارية
 * بلا أثر عملي. الجدول الأصلي (2026_07_24_213932) يبقى كما هو — هذا
 * migration منفصل يحذف الجدول بدل تعديل تاريخ سبق تنفيذه على أي بيئة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('university_records');
    }

    public function down(): void
    {
        Schema::create('university_records', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique();
            $table->enum('type', ['student', 'staff']);
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
        });
    }
};
