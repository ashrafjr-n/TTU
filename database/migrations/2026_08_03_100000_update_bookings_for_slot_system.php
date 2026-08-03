<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * يضيف حبيبية 5 دقائق للحجوزات (booking_minute)، بالإضافة إلى مفتاح
     * active_slot_key لضمان عدم ازدواج الحجز على مستوى قاعدة البيانات
     * (null دائمًا إلا للحجوزات confirmed، وقاعدة SQL تسمح بتكرار NULL
     * ضمن unique index — بعكس تكرار نفس المفتاح النصي).
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('booking_minute')->default(0)->after('booking_hour');
            $table->string('active_slot_key')->nullable()->unique()->after('status');
        });

        DB::table('bookings')
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->get()
            ->each(function ($booking) {
                DB::table('bookings')->where('id', $booking->id)->update([
                    'active_slot_key' => "{$booking->booking_date}|{$booking->booking_hour}|{$booking->booking_minute}",
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_minute', 'active_slot_key']);
        });
    }
};
