<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Notifications\ClinicReplyReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * رد الإدارة على رسالة وصلت عبر فورم "تواصل" — يُرسَل من صندوق الوارد
     * بلوحة الإدارة (فورم عادي ثم رجوع بفلاش، كبقية صفحات اللوحة).
     *
     * المسار نفسه محمي بـrole:admin، ويبقى الشرط هنا أن الرسالة موجَّهة
     * فعلًا للإدارة — كي لا يُستعمل المسار لحقن رد على رسالة خاصة بين
     * مستخدمين آخرين لو ظهرت رسائل كهذه مستقبلًا.
     */
    public function reply(Request $request, Message $message): RedirectResponse
    {
        abort_unless($message->recipient->isAdmin(), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:'.Message::MAX_BODY_LENGTH,
        ], [
            'body.required' => __('admin_messages.errors.body_required'),
            'body.max' => __('admin_messages.errors.body_max', ['max' => Message::MAX_BODY_LENGTH]),
        ]);

        $reply = Message::create([
            // المُرسِل هو المدير الذي رد فعلًا، لا بالضرورة صاحب الحساب
            // المستقبِل للرسالة الأصلية
            'sender_id' => $request->user()->id,
            'recipient_id' => $message->sender_id,
            'body' => $validated['body'],
            'parent_message_id' => $message->id,
        ]);

        $reply->recipient->notify(new ClinicReplyReceived($reply));

        return back()->with('success', __('admin_messages.flash.reply_sent'));
    }
}
