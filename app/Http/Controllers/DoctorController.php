<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\DoctorAttendance;
use App\Models\DoctorDayAssignment;
use App\Models\Medication;
use App\Models\VisitReport;
use App\Notifications\BookingCancelledByClinic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    /**
     * عرض لوحة الدكتور — الأسبوع الجاري كاملًا (سبت→جمعة، راجع
     * Booking::currentWeekDates)، مقتصرًا على الأيام المُعيَّنة لهذا الدكتور
     * تحديدًا (DoctorDayAssignment) لا كل أيام الأسبوع. يشمل أيامًا ماضية
     * ضمن الأسبوع (مثلًا الأحد لو اليوم الأربعاء) لا القادمة فقط — بعكس
     * صفحة الحجز (نافذة 3 أيام قادمة) التي تبقى كما هي.
     */
    public function index()
    {
        $doctorId = Auth::id();
        $assignedDaysOfWeek = DoctorDayAssignment::where('doctor_id', $doctorId)->pluck('day_of_week');

        $weekDates = Booking::currentWeekDates();
        $assignedDates = collect($weekDates)
            ->filter(fn (Carbon $date) => $assignedDaysOfWeek->contains($date->dayOfWeek))
            ->values();

        // استعلام واحد لكل حجوزات الأسبوع (مفهرس بمدى التاريخ) بدل استعلام
        // منفصل لكل يوم مُعيَّن — يومان مُعيَّنان يعنيان استعلامين بدل واحد،
        // وقد تصل الأيام المُعيَّنة لخمسة (أحد–خميس). whereBetween (لا
        // whereIn) عمدًا: عمود booking_date قد يُخزَّن بصيغة تتضمن وقتًا
        // ("Y-m-d H:i:s") حسب قاعدة البيانات، فمطابقة whereIn الحرفية على
        // "Y-m-d" فقط تفشل بصمت — مقارنة المدى تبقى صحيحة لأن الترتيب
        // النصي يطابق الترتيب الزمني (نفس الأسلوب المستخدم أصلًا بمخطط لوحة
        // المدير الأسبوعي). أيام الأسبوع غير المُعيَّنة لهذا الدكتور تُجلَب
        // ضمن هذا المدى أيضًا لكنها تُهمَل لاحقًا (لا تُستخدم إلا حجوزات
        // $assignedDates)، فتكلفتها إهمال بيانات محمَّلة أصلًا، لا استعلام إضافي.
        $bookingsByDate = Booking::with(['user', 'visitReport.medications'])
            ->whereBetween('booking_date', [$weekDates[0]->toDateString(), $weekDates[6]->toDateString()])
            ->where('status', 'confirmed')
            ->orderBy('booking_hour')
            ->orderBy('booking_minute')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->booking_date->toDateString());

        $days = [];
        $defaultIndex = 0;

        foreach ($assignedDates as $index => $date) {
            if ($date->isToday()) {
                $defaultIndex = $index;
            }

            $days[] = [
                'index' => $index,
                'date' => $date->toDateString(),
                'label' => Booking::weekDayLabel($date),
                'is_today' => $date->isToday(),
                'bookings' => $bookingsByDate->get($date->toDateString(), collect()),
            ];
        }

        $this->attachPatientHistory($days);

        // name_ar/name_en (لا name) لازم تكون ضمن الأعمدة المحمَّلة صراحة —
        // اختصار الأعمدة هنا يعني أن سمة name المشتقة (Medication::name())
        // ستبقى null لو غابا، رغم أن name نفسها ليست عمودًا فعليًا فيقدر
        // المرء ينساها بسهولة عند تعديل هذا الاستعلام مستقبلًا.
        $medications = Medication::where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'unit', 'stock_quantity']);

        $todayAttendance = DoctorAttendance::where('doctor_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        $todayEntry = collect($days)->firstWhere('is_today', true);

        return view('doctor.dashboard', [
            'days' => $days,
            'defaultDayIndex' => $defaultIndex,
            'todayBookingsCount' => $todayEntry ? $todayEntry['bookings']->count() : 0,
            'medications' => $medications,
            'todayAttendance' => $todayAttendance,
        ]);
    }

    /**
     * يُلحق بكل حجز معروض تاريخ زيارات صاحبه السابقة (باستثناء تقرير هذا
     * الحجز نفسه)، جاهزًا كمصفوفة لمودال التقرير — مرة واحدة هنا بدل حساب
     * نفس الشيء مرتين بالعرض (بطاقات الحجوزات + إعادة فتح المودال بعد خطأ
     * تحقّق)، وبدل استعلام منفصل لكل حجز (N+1) على أيام الدكتور المعروضة.
     */
    private function attachPatientHistory(array $days): void
    {
        $bookings = collect($days)->flatMap(fn (array $day) => $day['bookings']);
        $patientIds = $bookings->pluck('user_id')->unique();

        $reportsByPatient = VisitReport::with(['medications', 'booking'])
            ->whereHas('booking', fn ($q) => $q->whereIn('user_id', $patientIds))
            ->get()
            ->groupBy(fn (VisitReport $report) => $report->booking->user_id);

        foreach ($bookings as $booking) {
            $booking->patientHistory = ($reportsByPatient->get($booking->user_id) ?? collect())
                ->reject(fn (VisitReport $report) => $report->booking_id === $booking->id)
                ->sortByDesc(fn (VisitReport $report) => $report->booking->slotStart())
                ->values()
                ->map(fn (VisitReport $report) => [
                    'dateLabel' => $report->booking->booking_date->translatedFormat('d F Y'),
                    'condition' => $report->condition,
                    'diagnosis' => $report->diagnosis,
                    'medications' => $report->medications->pluck('name')->all(),
                ])
                ->all();
        }
    }

    /**
     * تسجيل انصراف الدكتور لنفس صف الحضور اليومي.
     *
     * الحضور نفسه يُسجَّل تلقائيًا عند تسجيل الدخول (راجع
     * App\Listeners\RecordDoctorAttendanceOnLogin) — الانصراف يبقى إجراءً
     * يدويًا لأن مغادرة العيادة قرار صريح لا يمكن استنتاجه من الجلسة.
     */
    public function checkOut()
    {
        $attendance = DoctorAttendance::where('doctor_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$attendance) {
            return back()->with('error', __('doctor.attendance.check_in_required'));
        }

        if ($attendance->check_out_at) {
            return back()->with('error', __('doctor.attendance.already_checked_out'));
        }

        $attendance->update(['check_out_at' => now()]);

        ActivityLog::record(Auth::id(), 'doctor_check_out', 'activity_log.doctor_check_out');

        return back()->with('success', __('doctor.attendance.check_out_success'));
    }

    /**
     * إلغاء حجز مؤكد (من جدول الدكتور).
     *
     * الفاعل هنا الدكتور لا المريض، لذلك: نتحقق أولًا أن الحجز ما زال مؤكدًا
     * (كي لا يُلغى حجز ملغى مسبقًا فيُرسَل إشعار مكرر للمريض)، ثم أن يوم هذا
     * الحجز مُعيَّن لهذا الدكتور تحديدًا (403 غير ذلك — لا يقدر دكتور يلغي
     * حجزًا بيوم دكتور آخر حتى لو عرف رقم الحجز بالرابط)، نسجّل الحدث في
     * سجل النشاط تحت حساب الدكتور مع ذكر اسم المريض، ونُشعر المريض بالإلغاء —
     * وإلا لن يعرف أن موعده أُلغي وقد يحضر للعيادة.
     */
    public function cancelBooking(Booking $booking)
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', __('doctor.bookings_table.not_cancellable'));
        }

        abort_unless(
            DoctorDayAssignment::doctorIdForDate($booking->booking_date) === Auth::id(),
            403,
            __('doctor.errors.not_your_day')
        );

        $booking->update(['status' => 'cancelled']);

        ActivityLog::record(
            Auth::id(),
            'booking_cancelled_by_doctor',
            'activity_log.booking_cancelled_by_doctor',
            [
                'patient' => $booking->user->name,
                'date' => $booking->booking_date->toDateString(),
                'time' => $booking->timeLabel(),
            ]
        );

        $booking->user->notify(new BookingCancelledByClinic($booking));

        return back()->with('success', __('doctor.bookings_table.cancel_success'));
    }
}
