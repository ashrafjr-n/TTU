<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تذكير الموعد صار نوعين مستقلين (قبل ساعة تقريبًا، وقبل 15 دقيقة تقريبًا)
 * بدل نوع واحد — عمود reminded_at الفردي كان يمنع إرسال أكثر من تذكير واحد
 * لنفس الحجز (أول تذكير يُرسَل يُسكِت أي تذكير لاحق). rename بدل إضافة
 * عمود جديد يحافظ على القيم القديمة (تذكيرات الساعة المُرسَلة فعلًا قبل هذا
 * الإصدار) تحت اسمها الجديد المطابق لمعناها الفعلي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('reminded_at', 'reminder_1h_sent_at');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('reminder_15m_sent_at')->nullable()->after('reminder_1h_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reminder_15m_sent_at');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('reminder_1h_sent_at', 'reminded_at');
        });
    }
};
