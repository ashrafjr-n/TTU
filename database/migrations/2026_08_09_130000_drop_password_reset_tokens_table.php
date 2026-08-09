<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إلغاء تدفق "نسيت كلمة المرور" بالكامل — الحسابات صارت تستخدم بريدًا
 * اصطناعيًا/غير قابل للوصول (رقم جامعي/وظيفي هو بيانات الدخول الفعلية)،
 * فرابط إعادة التعيين عبر البريد أصلًا معطّل ولا بديل مخطَّط له حاليًا.
 * الجدول الأصلي (0001_01_01_000000) يبقى كما هو — هذا migration منفصل
 * يحذف الجدول بدل تعديل تاريخ سبق تنفيذه على أي بيئة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
