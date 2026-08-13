<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Notifications\AdminMessageReceived;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    /**
     * فورم "تواصل" — طالب أو موظف يراسل إدارة العيادة. لا اختيار للمستقبِل:
     * كل الرسائل تذهب للإدارة، وهذا مذكور صراحةً بالصفحة كي يعرف المرسِل من
     * سيقرأ رسالته. الاسم دائمًا اسم الحساب المسجَّل دخوله (الحقل معطّل
     * بالفورم)، فلا داعي لقبوله من الطلب.
     */
    public function create(): View
    {
        return view('contact', ['maxBodyLength' => Message::MAX_BODY_LENGTH]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:'.Message::MAX_BODY_LENGTH,
        ], [
            'message.required' => __('contact.errors.message_required'),
            'message.max' => __('contact.errors.message_max', ['max' => Message::MAX_BODY_LENGTH]),
        ]);

        $admins = User::where('role', 'admin')->orderBy('id')->get();

        // بلا حساب مدير واحد على الأقل لا يوجد مستقبِل للرسالة — حالة لا
        // تحدث بتثبيت سليم (البذور تنشئ المدير)، فنوقف الطلب بدل تخزين
        // رسالة بلا مستقبِل تضيع بصمت.
        abort_if($admins->isEmpty(), 503);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            // المستقبِل الرسمي هو أول حساب مدير — وصندوق الوارد بلوحة
            // الإدارة يعرض رسائل أي حساب مدير، فلا تُحجب عن البقية.
            'recipient_id' => $admins->first()->id,
            'body' => $validated['message'],
        ]);

        // كل المديرين يُشعَرون، لا صاحب الحساب المستقبِل وحده — أول من يفتح
        // اللوحة يقدر يرد.
        Notification::send($admins, new AdminMessageReceived($message));

        return back()->with('success', __('contact.flash.sent_success'));
    }
}
