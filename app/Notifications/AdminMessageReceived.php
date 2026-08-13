<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * رسالة من طالب/موظف لإدارة العيادة عبر فورم "تواصل" — تظهر في جرس إشعارات
 * المدير مع اسم المرسل ومقتطف النص، والضغط عليها يفتح صندوق وارد الرسائل
 * بلوحة الإدارة حيث يمكن قراءتها كاملة والرد عليها.
 */
class AdminMessageReceived extends Notification
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
            'type' => 'admin_message',
            'title_key' => 'notifications.admin_message.title',
            'title_params' => ['name' => $this->message->sender->name],
            // نص الرسالة كتبه المستخدم مباشرة، فلا يُترجَم
            'body' => $this->message->body,
            'url' => route('admin.messages'),
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender->name,
        ];
    }
}
