<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * رسالة من طالب/موظف لدكتور عبر فورم "تواصل" — تظهر في جرس إشعارات الدكتور
 * مع اسم المرسل ونص الرسالة، ويقدر يرد عليها مباشرة من نفس لوحة الإشعارات.
 */
class DoctorMessageReceived extends Notification
{
    use Queueable;

    public function __construct(protected Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'doctor_message',
            'title' => 'رسالة من '.$this->message->sender->name,
            'body' => $this->message->body,
            'url' => null,
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender->name,
        ];
    }
}
