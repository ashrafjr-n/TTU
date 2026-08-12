<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * تذكير بموعد قادم — يُرسَل مرتين مستقلتين لكل حجز (قبل ساعة تقريبًا، وقبل
 * 15 دقيقة تقريبًا؛ راجع SendAppointmentReminders)، بنص مختلف لكل منهما كي
 * يعرف المستخدم أي تذكير هذا بالضبط دون التباس بينهما.
 */
class AppointmentReminder extends Notification
{
    use Queueable;

    /**
     * @param  '1h'|'15m'  $leadTime
     */
    public function __construct(protected Booking $booking, protected string $leadTime = '1h')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'reminder',
            'title_key' => 'notifications.reminder.title',
            'body_key' => $this->leadTime === '15m'
                ? 'notifications.reminder.body_15m'
                : 'notifications.reminder.body',
            'body_params' => ['time' => $this->booking->timeLabel()],
            'url' => null,
        ];
    }
}
