<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "أي طبيب مسؤول عن أي يوم؟" — منفصل تمامًا عن doctor_schedules.working_days
 * (الذي يبقى لغرضه الأصلي: التوظيف/الحضور، أي الأيام التي يُتوقَّع فيها
 * حضور الطبيب عمومًا). هذا الجدول هو مصدر الحقيقة الوحيد المستخدم لتحديد
 * أي حجز يخص أي طبيب (عبر يوم أسبوع booking_date)، لعرض لوحة الطبيب
 * وصلاحيات الإلغاء/التقرير — لا عمود doctor_id على bookings؛ الملكية
 * تُشتق دائمًا من booking_date عند الاستعلام.
 *
 * day_of_week فريد (يوم واحد بحد أقصى طبيب واحد بأي وقت)، بمعيار
 * Carbon::dayOfWeek (0 = الأحد ... 6 = السبت) — نفس المعيار المستخدم في
 * doctor_schedules.working_days وBooking::dayLabel(). العيادة تعمل الأحد
 * للخميس فقط (0-4)؛ القيد على ذلك مفروض على مستوى التطبيق
 * (AdminController + DoctorDayAssignment::CLINIC_DAYS) لا قاعدة البيانات،
 * لإبقاء الجدول بسيطًا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_day_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->unique();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_day_assignments');
    }
};
