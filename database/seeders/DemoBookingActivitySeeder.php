<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * بيانات تجريبية صغيرة وثابتة (بدون عشوائية) لملء لوحة إحصائيات المدير
 * بحجوزات تاريخية حقيقية الشكل، بما إنو قاعدة البيانات المحلية لم تتراكم
 * فيها حجوزات كافية بعد. كل صف هنا محدد يدويًا (تاريخ/ساعة/دقيقة/دور)
 * وليس نتيجة توليد عشوائي — راجع تقرير التسليم لتفاصيل ما أُضيف بالضبط.
 */
class DemoBookingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('role', 'student')->first();
        $staff = User::where('role', 'staff')->first();

        if (!$student || !$staff) {
            return;
        }

        // [تاريخ نسبي لليوم الحالي (أيام للخلف), الساعة, الدقيقة, دور المستخدم]
        $rows = [
            [1, 8, 0, 'student'],   // أمس
            [1, 9, 45, 'staff'],
            [1, 11, 15, 'student'],
            [4, 9, 5, 'student'],
            [4, 10, 50, 'staff'],
            [5, 8, 10, 'student'],
            [5, 13, 0, 'student'],
            [7, 10, 20, 'student'],
            [7, 9, 50, 'staff'],
        ];

        foreach ($rows as [$daysAgo, $hour, $minute, $role]) {
            $date = Carbon::today()->subDays($daysAgo)->toDateString();
            $user = $role === 'staff' ? $staff : $student;

            $exists = Booking::where('booking_date', $date)
                ->where('booking_hour', $hour)
                ->where('booking_minute', $minute)
                ->where('status', 'confirmed')
                ->exists();

            if ($exists) {
                continue;
            }

            Booking::create([
                'user_id' => $user->id,
                'booking_date' => $date,
                'booking_hour' => $hour,
                'booking_minute' => $minute,
                'price' => Booking::PRICE,
                'status' => 'confirmed',
            ]);
        }
    }
}
