<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * محاكاة استقبال رسالة تواصل — لا يوجد إرسال بريد فعلي، فقط إشعار داخلي
 * لكل حساب مدير، بنفس آلية الإشعارات المستخدمة أصلًا بالتطبيق (تذكير
 * المواعيد، تقرير الزيارة). يظهر في جرس الإشعارات كأي إشعار آخر.
 */
class ContactFormSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected string $senderName, protected string $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contact_message',
            'title' => 'رسالة تواصل جديدة',
            'body' => "{$this->senderName}: ".Str::limit($this->message, 120),
            'url' => null,
        ];
    }
}
