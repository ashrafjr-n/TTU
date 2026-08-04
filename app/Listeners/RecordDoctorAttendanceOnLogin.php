<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\DoctorAttendance;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * تسجيل حضور الدكتور تلقائيًا بمجرد تسجيل دخوله — لا يوجد زر "تسجيل حضور"
 * يدوي بعد الآن. مجرد الدخول للنظام يعني أن الدكتور على رأس عمله، فيظهر
 * فورًا كـ"متواجد الآن" للمدير.
 *
 * الاستماع لحدث Login (بدل وضع المنطق داخل AuthenticatedSessionController)
 * يغطي كل مسارات الدخول — فورم الدخول، وكوكي "تذكرني" — بمكان واحد.
 *
 * ملاحظة مقصودة: لو كان يوجد صف حضور لليوم أصلًا، لا نلمسه إطلاقًا. هذا
 * يعني أن دكتورًا سجّل انصرافه ثم دخل مجددًا لا يُعاد فتح دوامه تلقائيًا،
 * ولا يُعاد ضبط وقت حضوره الأصلي — وقت الحضور يبقى أول دخول باليوم.
 */
class RecordDoctorAttendanceOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user instanceof User || !$user->isDoctor()) {
            return;
        }

        // حساب معطّل: Auth::attempt ينجح (فيُطلق حدث Login) قبل أن يفحص
        // LoginRequest حالة التفعيل ويستدعي Auth::logout — أي أن هذا الحدث
        // يصل هنا حتى للحسابات المعطّلة. بدون هذا الفحص كنا سنسجّل حضورًا
        // لدكتور مُعطّل لم يدخل النظام فعليًا.
        if (!$user->is_active) {
            return;
        }

        // ملاحظة مهمة: firstOrCreate(['date' => '2026-08-04']) لا تصلح هنا.
        // عمود date مُهيّأ كـ date cast فيُخزَّن بصيغة "Y-m-d H:i:s"
        // ("2026-08-04 00:00:00")، بينما شرط البحث يُرسل "2026-08-04" نصًا —
        // فلا يتطابقان أبدًا، وتحاول الدالة الإدراج في كل مرة فتضرب قيد
        // unique(doctor_id, date). لذلك نقرأ بـ whereDate (نفس النمط المتبع
        // في باقي التطبيق) ثم نُنشئ عند الحاجة.
        $exists = DoctorAttendance::where('doctor_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->exists();

        if ($exists) {
            return;
        }

        try {
            DoctorAttendance::create([
                'doctor_id' => $user->id,
                'date' => Carbon::today(),
                'check_in_at' => now(),
            ]);
        } catch (QueryException $e) {
            // تسابق نادر (دخولان متزامنان): قيد unique هو الحكم النهائي —
            // الحضور مسجَّل فعلًا، فلا داعي لإفشال عملية الدخول نفسها.
            return;
        }

        ActivityLog::record($user->id, 'doctor_check_in', 'activity_log.doctor_check_in');
    }
}
