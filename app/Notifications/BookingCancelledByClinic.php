<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * يُرسَل للمريض (طالب/موظف) عندما يلغي الدكتور حجزه من لوحة الدكتور —
 * بدون هذا الإشعار كان المريض لا يعلم بالإلغاء إطلاقًا وقد يحضر لموعد ملغى.
 */
class BookingCancelledByClinic extends Notification
{
    use Queueable;

    public function __construct(protected Booking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'title_key' => 'notifications.booking_cancelled.title',
            'body_key' => 'notifications.booking_cancelled.body',
            'body_params' => [
                'date' => $this->booking->booking_date->translatedFormat('d F Y'),
                'time' => $this->booking->timeLabel(),
            ],
            // رابط صفحة الحجز ليقدر يحجز موعدًا بديلًا مباشرة
            'url' => route('booking.index', [], false),
        ];
    }
}
