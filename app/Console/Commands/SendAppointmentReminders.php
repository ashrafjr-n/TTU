<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\AppointmentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAppointmentReminders extends Command
{
    /**
     * يعمل كل 5 دقائق — راجع bootstrap/app.php. نافذة كل تذكير عرضها 10
     * دقائق (ضعف دورة التشغيل) لضمان التقاط كل خانة (متعددات 5 دقائق) في
     * تشغيل واحد على الأقل، بصرف النظر عن أي انزياح بسيط بين وقت التشغيل
     * الفعلي والدقيقة الصحيحة.
     */
    protected $signature = 'notifications:send-reminders';

    protected $description = 'إرسال تذكير للحجوزات المؤكدة التي يقترب موعدها (قبل ساعة تقريبًا، وقبل 15 دقيقة تقريبًا)';

    public function handle(): int
    {
        $now = now();

        $count = $this->sendWindow($now, 'reminder_1h_sent_at', 55, 65, '1h');
        $count += $this->sendWindow($now, 'reminder_15m_sent_at', 10, 20, '15m');

        $this->info("تم إرسال {$count} تذكير.");

        return self::SUCCESS;
    }

    /**
     * يرسل تذكيرًا (leadTime) لكل حجز مؤكد يقع بدء موعده ضمن نافذة
     * [الآن+startMinutes .. الآن+endMinutes]، ولم يُرسَل له هذا النوع من
     * التذكير بعد ($column مستقل عن عمود التذكير الآخر، فالحجز الواحد يقدر
     * ياخذ التذكيرين كل على حدة). لا يُرسَل شيء لدكاترة — الحجوزات أصلًا
     * حكرًا على الطلاب/الموظفين (بوابة /booking تفرض role:student,staff)،
     * لكن الفحص هنا صريح كشرط أمان إضافي بدل الاعتماد ضمنيًا على ذلك فقط.
     */
    private function sendWindow(Carbon $now, string $column, int $startMinutes, int $endMinutes, string $leadTime): int
    {
        $windowStart = $now->copy()->addMinutes($startMinutes);
        $windowEnd = $now->copy()->addMinutes($endMinutes);

        $bookings = Booking::with('user')
            ->where('status', 'confirmed')
            ->whereNull($column)
            ->whereDate('booking_date', today())
            ->get()
            ->filter(fn (Booking $booking) => $booking->slotStart()->between($windowStart, $windowEnd))
            ->reject(fn (Booking $booking) => $booking->user->isDoctor());

        $count = 0;

        foreach ($bookings as $booking) {
            $booking->user->notify(new AppointmentReminder($booking, $leadTime));
            $booking->update([$column => now()]);
            $count++;
        }

        return $count;
    }
}
