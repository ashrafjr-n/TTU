<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * رد إدارة العيادة على رسالة "تواصل" — يظهر في جرس إشعارات الطالب/الموظف
 * صاحب الرسالة الأصلية.
 *
 * بعكس بقية الإشعارات، هذا الإشعار لا يحمل نص الرد إطلاقًا: لا في العنوان
 * ولا في المتن، ولا حتى مخزَّنًا ضمن بياناته. يكتفي بإخبار المستخدم أن ردًا
 * وصله، والنص نفسه لا يُقرأ إلا داخل لوحة الرسالة التي تُفتح بالضغط عليه
 * (راجع partials/message-panel) — كي لا يظهر محتوى طبي/شخصي في قائمة
 * الإشعارات المنسدلة.
 */
class ClinicReplyReceived extends Notification
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
            'type' => 'clinic_reply',
            'title_key' => 'notifications.clinic_reply.title',
            'body_key' => 'notifications.clinic_reply.body',
            // لا 'url': الضغط يفتح لوحة داخل الصفحة لا ينتقل لصفحة أخرى
            'url' => null,
            'message_id' => $this->reply->id,
        ];
    }
}
