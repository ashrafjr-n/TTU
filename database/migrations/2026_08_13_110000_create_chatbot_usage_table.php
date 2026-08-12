<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * عدّاد استهلاك مساعد الدعم — صف واحد لكل يوم. اختيار "صف لكل تاريخ" بدل
 * عدّاد واحد يُصفَّر بمهمة مجدولة مقصود: التصفير يصير ضمنيًا بمجرد تغيّر
 * التاريخ (لا مهمة cron قد تفشل فتُقفل المحادثة يومًا كاملًا)، ويبقى سجل
 * الاستهلاك التاريخي متاحًا للمراجعة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_usage', function (Blueprint $table) {
            $table->id();
            // فريد — هو ما يجعل "حصة اليوم" صفًّا واحدًا لا أكثر، حتى لو
            // تسابق طلبان على إنشائه في أول طلب باليوم (راجع ChatbotUsage::reserveSlot)
            $table->date('usage_date')->unique();
            $table->unsignedInteger('requests_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_usage');
    }
};
