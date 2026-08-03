<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\AppointmentReminder;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * يعمل كل 5 دقائق — راجع bootstrap/app.php. نافذة 55-65 دقيقة قبل
     * الموعد تضمن التقاط كل خانة (متعددات 5 دقائق) في تشغيل واحد على الأقل.
     */
    protected $signature = 'notifications:send-reminders';

    protected $description = 'إرسال تذكير للحجوزات المؤكدة التي يقترب موعدها (خلال ساعة تقريبًا)';

    public function handle(): int
    {
        $windowStart = now()->addMinutes(55);
        $windowEnd = now()->addMinutes(65);

        $bookings = Booking::where('status', 'confirmed')
            ->whereNull('reminded_at')
            ->whereDate('booking_date', today())
            ->get()
            ->filter(fn (Booking $booking) => $booking->slotStart()->between($windowStart, $windowEnd));

        $count = 0;

        foreach ($bookings as $booking) {
            $booking->user->notify(new AppointmentReminder($booking));
            $booking->update(['reminded_at' => now()]);
            $count++;
        }

        $this->info("تم إرسال تذكير لـ {$count} حجز.");

        return self::SUCCESS;
    }
}
