<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification
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
            'type' => 'reminder',
            'title_key' => 'notifications.reminder.title',
            'body_key' => 'notifications.reminder.body',
            'body_params' => ['time' => $this->booking->timeLabel()],
            'url' => null,
        ];
    }
}
