<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\DoctorAttendance;
use App\Models\Medication;
use App\Models\VisitReport;
use App\Notifications\BookingCancelledByClinic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    /**
     * عرض لوحة الدكتور — بنفس نافذة الأيام الثلاثة (اليوم + يومين قادمين)
     * المتاحة للطلاب/الموظفين بصفحة الحجز، بدل تاريخ واحد يُختار عبر حقل حر.
     */
    public function index()
    {
        $days = [];
        foreach (Booking::bookableDates() as $index => $date) {
            $dayBookings = Booking::with(['user', 'visitReport.medications'])
                ->whereDate('booking_date', $date->toDateString())
                ->where('status', 'confirmed')
                ->orderBy('booking_hour')
                ->orderBy('booking_minute')
                ->get();

            $days[] = [
                'index' => $index,
                'date' => $date->toDateString(),
                'label' => Booking::dayLabel($index, $date),
                'bookings' => $dayBookings,
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

        return view('doctor.dashboard', [
            'days' => $days,
            'todayBookingsCount' => $days[0]['bookings']->count(),
            'medications' => $medications,
            'todayAttendance' => $todayAttendance,
        ]);
    }

    /**
     * يُلحق بكل حجز معروض تاريخ زيارات صاحبه السابقة (باستثناء تقرير هذا
     * الحجز نفسه)، جاهزًا كمصفوفة لمودال التقرير — مرة واحدة هنا بدل حساب
     * نفس الشيء مرتين بالعرض (بطاقات الحجوزات + إعادة فتح المودال بعد خطأ
     * تحقّق)، وبدل استعلام منفصل لكل حجز (N+1) على 3 أيام من الحجوزات.
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
     * (كي لا يُلغى حجز ملغى مسبقًا فيُرسَل إشعار مكرر للمريض)، نسجّل الحدث في
     * سجل النشاط تحت حساب الدكتور مع ذكر اسم المريض، ونُشعر المريض بالإلغاء —
     * وإلا لن يعرف أن موعده أُلغي وقد يحضر للعيادة.
     */
    public function cancelBooking(Booking $booking)
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', __('doctor.bookings_table.not_cancellable'));
        }

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
