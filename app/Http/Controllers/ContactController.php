<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Notifications\DoctorMessageReceived;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * فورم "تواصل" — طالب أو موظف يختار أحد الأطباء ويرسله رسالة. الاسم
     * دائمًا اسم الحساب المسجَّل دخوله (الحقل معطّل بالفورم)، فلا داعي
     * لقبوله من الطلب.
     */
    public function create(): View
    {
        return view('contact', [
            'doctors' => User::where('role', 'doctor')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|integer|exists:users,id',
            'message' => 'required|string|max:2000',
        ], [
            'doctor_id.required' => __('contact.errors.doctor_required'),
            'message.required' => __('contact.errors.message_required'),
        ]);

        $doctor = User::where('role', 'doctor')->findOrFail($validated['doctor_id']);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $doctor->id,
            'body' => $validated['message'],
        ]);

        $doctor->notify(new DoctorMessageReceived($message));

        return back()->with('success', __('contact.flash.sent_success'));
    }
}
