<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ContactFormSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    /**
     * استقبال فورم "تواصل معنا" — الصفحة عامة (لا تشترط تسجيل دخول)،
     * فالاسم حقل حر دائمًا بدل الاعتماد على حساب مسجَّل. لا يوجد إرسال بريد
     * فعلي؛ الرسالة تصل كإشعار داخلي لكل حسابات المدير.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ], [
            'name.required' => 'الرجاء إدخال اسمك.',
            'message.required' => 'الرجاء كتابة رسالتك.',
        ]);

        Notification::send(
            User::where('role', 'admin')->get(),
            new ContactFormSubmitted($validated['name'], $validated['message'])
        );

        return back()->with('success', 'تم إرسال رسالتك بنجاح، سنتواصل معك قريبًا.');
    }
}
