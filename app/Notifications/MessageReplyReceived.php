<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * رد الدكتور على رسالة تواصل — يظهر في جرس إشعارات الطالب/الموظف صاحب
 * الرسالة الأصلية، بنفس آلية الإشعارات المعتادة.
 */
class MessageReplyReceived extends Notification
{
    use Queueable;

    public function __construct(protected Message $reply)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'message_reply',
            'title' => 'رد من '.$this->reply->sender->name,
            'body' => $this->reply->body,
            'url' => null,
            'message_id' => $this->reply->id,
            'sender_name' => $this->reply->sender->name,
        ];
    }
}
