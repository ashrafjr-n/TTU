<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فهارس ناقصة على أعمدة تُستعلَم بها كثيرًا — تحقّقنا فعليًا (نسخة PostgreSQL
 * محلية جديدة) أن foreignId()->constrained() لا يُنشئ فهرسًا تلقائيًا على
 * عمود المفتاح الأجنبي في Postgres (بعكس MySQL)؛ bookings.user_id
 * وactivity_logs.user_id كانا بلا أي فهرس رغم استعلامهما في كل صفحة تقريبًا.
 *
 * - bookings(user_id, status): يخدم findActiveFor/confirmedCountInSemester
 *   ($user->bookings()->where('status', ...))، وهي الاستعلامات الأكثر تكرارًا
 *   بالتطبيق (كل زيارة لصفحة الحجز أو لوحة الطالب/الموظف).
 * - bookings(booking_date, status): يخدم كل استعلام "حجوزات يوم/مدى تاريخ
 *   معيّن ومؤكدة" — صفحة الحجز، لوحة الدكتور، مخططات لوحة المدير، أمر
 *   التذكيرات، وفلترة السجل التاريخي بالإدارة.
 * - activity_logs(user_id): يخدم $user->activityLogs() (صفحة نشاط مستخدم
 *   بالإدارة) وربط whereHas('user', ...) بسجل نشاط الإدارة العام.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index(['booking_date', 'status']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['booking_date', 'status']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
