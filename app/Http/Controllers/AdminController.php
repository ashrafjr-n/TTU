<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\DoctorAttendance;
use App\Models\DoctorSchedule;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * لوحة المدير الرئيسية — نظرة عامة
     */
    public function index()
    {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_staff' => User::where('role', 'staff')->count(),
            'total_doctors' => User::where('role', 'doctor')->count(),
            'today_bookings' => Booking::whereDate('booking_date', today())
                ->where('status', 'confirmed')
                ->count(),
        ];

        $dayLabels = __('common.days');

        // الأسبوع الحالي: أحد إلى سبت (بغض النظر عن لغة النظام)، متسق مع
        // تسمية الأيام المستخدمة في صفحة جدول عمل الأطباء
        $weekStart = Carbon::today()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);

        $dailyCounts = Booking::where('status', 'confirmed')
            ->whereBetween('booking_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->selectRaw('booking_date, count(*) as c')
            ->groupBy('booking_date')
            ->pluck('c', 'booking_date');

        $weekChart = ['labels' => [], 'data' => []];
        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $weekChart['labels'][] = $dayLabels[$d->dayOfWeek];
            $weekChart['data'][] = (int) ($dailyCounts[$d->toDateString()] ?? 0);
        }

        $weekBookingsTotal = array_sum($weekChart['data']);

        // طلاب مقابل موظفين خلال الأسبوع الحالي
        $roleCounts = Booking::join('users', 'users.id', '=', 'bookings.user_id')
            ->where('bookings.status', 'confirmed')
            ->whereBetween('bookings.booking_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->selectRaw('users.role, count(*) as c')
            ->groupBy('users.role')
            ->pluck('c', 'role');

        $roleChart = [
            'labels' => [__('admin_dashboard.students_series'), __('admin_dashboard.staff_series')],
            'data' => [(int) ($roleCounts['student'] ?? 0), (int) ($roleCounts['staff'] ?? 0)],
        ];

        // نسبة الإشغال لكل ساعة من ساعات الدوام الثمانية — على كامل السجل
        // التاريخي المتوفر (وليس أسبوعًا واحدًا) حتى تعكس نمطًا "معتادًا"
        $hourCounts = Booking::where('status', 'confirmed')
            ->selectRaw('booking_hour, count(*) as c')
            ->groupBy('booking_hour')
            ->pluck('c', 'booking_hour');

        $distinctDays = max(
            Booking::where('status', 'confirmed')->groupBy('booking_date')->pluck('booking_date')->count(),
            1
        );

        $slotsPerHour = count(Booking::STUDENT_MINUTES) + count(Booking::STAFF_MINUTES);
        $capacityPerHour = $slotsPerHour * $distinctDays;

        $hourlyChart = ['labels' => [], 'rates' => [], 'hours' => []];
        for ($h = Booking::OPEN_HOUR; $h < Booking::CLOSE_HOUR; $h++) {
            $booked = (int) ($hourCounts[$h] ?? 0);
            $rate = $capacityPerHour > 0 ? round($booked / $capacityPerHour * 100, 1) : 0;

            $hourlyChart['hours'][] = $h;
            $hourlyChart['labels'][] = sprintf('%d %s', $h > 12 ? $h - 12 : $h, $h < 12 ? __('common.time.am_short') : __('common.time.pm_short'));
            $hourlyChart['rates'][] = $rate;
        }

        // أكثر الساعات ازدحامًا — نفس بيانات الإشغال، مرتّبة تنازليًا
        $busiestHours = collect($hourlyChart['hours'])
            ->map(fn ($h, $i) => [
                'hour' => $h,
                'label' => $hourlyChart['labels'][$i],
                'rate' => $hourlyChart['rates'][$i],
            ])
            ->sortByDesc('rate')
            ->values()
            ->take(3);

        return view('admin.dashboard', [
            'stats' => $stats,
            'weekChart' => $weekChart,
            'weekBookingsTotal' => $weekBookingsTotal,
            'roleChart' => $roleChart,
            'hourlyChart' => $hourlyChart,
            'busiestHours' => $busiestHours,
        ]);
    }

    /**
     * عرض كل المستخدمين
     */
    public function users(Request $request)
    {
        $query = User::where('role', '!=', 'admin');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                // whereLike (not where(..., 'like', ...)) so the search stays
                // case-insensitive on Postgres too — a plain LIKE is
                // case-sensitive there, unlike SQLite/MySQL's default collation.
                $q->whereLike('name', "%{$search}%")
                  ->orWhereLike('email', "%{$search}%")
                  ->orWhereLike('identifier', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(15);

        return view('admin.users', compact('users'));
    }

    /**
     * تفعيل/تعطيل حساب مستخدم
     */
    public function toggleUserStatus(User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', __('admin_dashboard.errors.cannot_disable_admin'));
        }

        $user->update(['is_active' => !$user->is_active]);

        ActivityLog::record(
            Auth::id(),
            $user->is_active ? 'user_activated' : 'user_deactivated',
            $user->is_active ? 'activity_log.user_activated' : 'activity_log.user_deactivated',
            ['name' => $user->name]
        );

        return back()->with('success', $user->is_active
            ? __('admin_dashboard.flash.user_activated', ['name' => $user->name])
            : __('admin_dashboard.flash.user_deactivated', ['name' => $user->name]));
    }

    /**
     * عرض صفحة إضافة دكتور جديد
     */
    public function createDoctor()
    {
        return view('admin.create-doctor');
    }

    /**
     * حفظ دكتور جديد — أيام العمل الأسبوعية تُعيَّن هنا مع الحساب نفسه،
     * فصفحة الحضور صارت للعرض فقط.
     */
    public function storeDoctor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => ['required', 'digits:3', 'unique:'.User::class.',identifier'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|min:0|max:6',
        ]);

        $doctor = User::create([
            'name' => $validated['name'],
            'email' => $this->syntheticDoctorEmail($validated['identifier']),
            'password' => Hash::make($validated['password']),
            'role' => 'doctor',
            'identifier' => $validated['identifier'],
        ]);

        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'working_days' => $this->normalizeWorkingDays($validated['working_days'] ?? []),
        ]);

        ActivityLog::record(Auth::id(), 'doctor_created', 'activity_log.doctor_created', [
            'name' => $validated['name'],
            'identifier' => $validated['identifier'],
        ]);

        return redirect()->route('admin.users')->with('success', __('admin_dashboard.flash.doctor_created'));
    }

    /**
     * عرض صفحة تعديل حساب دكتور (البيانات الأساسية + أيام العمل الأسبوعية)
     */
    public function editDoctor(User $doctor)
    {
        abort_unless($doctor->isDoctor(), 404);

        $doctor->load('doctorSchedule');

        return view('admin.edit-doctor', compact('doctor'));
    }

    /**
     * تحديث حساب دكتور — الاسم/البريد + أيام العمل. هذا المكان الوحيد الذي
     * تُعدَّل منه أيام العمل بعد إنشاء الحساب.
     */
    public function updateDoctor(Request $request, User $doctor)
    {
        abort_unless($doctor->isDoctor(), 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => ['required', 'digits:3', Rule::unique('users', 'identifier')->ignore($doctor->id)],
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|min:0|max:6',
        ]);

        $doctor->update([
            'name' => $validated['name'],
            'identifier' => $validated['identifier'],
            // البريد للأطباء داخلي بحت (لا يُستخدم للدخول) — موجود فقط لإرضاء
            // قيد "email" الفريد على جدول users. يُشتق من الرقم الوظيفي نفسه
            // فيبقى متزامنًا معه دائمًا، بدل العكس كما كان سابقًا.
            'email' => $this->syntheticDoctorEmail($validated['identifier']),
        ]);

        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctor->id],
            ['working_days' => $this->normalizeWorkingDays($validated['working_days'] ?? [])]
        );

        ActivityLog::record(Auth::id(), 'doctor_updated', 'activity_log.doctor_updated', ['name' => $doctor->name]);

        return redirect()->route('admin.users')->with('success', __('admin_dashboard.flash.doctor_updated', ['name' => $doctor->name]));
    }

    /**
     * بريد داخلي فريد للطبيب — لا يُعرض ولا يُستخدم للدخول، فقط يُرضي قيد
     * "email" الفريد على جدول users (الدخول الفعلي بالرقم الوظيفي وحده).
     */
    private function syntheticDoctorEmail(string $identifier): string
    {
        return "doctor-{$identifier}@ttu.edu.jo";
    }

    /**
     * قيم checkbox تصل كنصوص دائمًا — لازم تحويلها لأرقام صحيحة، لأن
     * isWorkingOn() تقارنها بـ Carbon::dayOfWeek بمقارنة صارمة (strict)
     */
    private function normalizeWorkingDays(array $days): array
    {
        return collect($days)->map(fn ($day) => (int) $day)->unique()->sort()->values()->all();
    }

    /**
     * عرض كتالوج الأدوية
     */
    public function medications()
    {
        $medications = Medication::orderBy('name_ar')->paginate(15);

        return view('admin.medications', compact('medications'));
    }

    /**
     * إضافة دواء جديد للكتالوج — الاسمان (عربي/إنجليزي) مطلوبان معًا كي
     * يبقى الدواء معروضًا بشكل صحيح بأي لغة يتصفح بها أي مستخدم لاحقًا.
     */
    public function storeMedication(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255|unique:medications,name_ar',
            'name_en' => 'required|string|max:255|unique:medications,name_en',
            'stock_quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        $medication = Medication::create($validated);

        ActivityLog::record(Auth::id(), 'medication_added', 'activity_log.medication_added', ['name' => $medication->name]);

        return back()->with('success', __('admin_dashboard.flash.medication_added'));
    }

    /**
     * تعديل بيانات دواء (الاسمين/الوحدة/حد التنبيه) — لا يغيّر الكمية،
     * فذلك مسؤولية "إضافة كمية" حصرًا
     */
    public function updateMedication(Request $request, Medication $medication)
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('medications', 'name_ar')->ignore($medication->id)],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('medications', 'name_en')->ignore($medication->id)],
            'unit' => 'nullable|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        $medication->update($validated);

        ActivityLog::record(Auth::id(), 'medication_edited', 'activity_log.medication_edited', ['name' => $medication->name]);

        return back()->with('success', __('admin_dashboard.flash.medication_edited'));
    }

    /**
     * إضافة كمية لمخزون دواء موجود (الإجراء الأكثر استخدامًا)
     */
    public function restockMedication(Request $request, Medication $medication)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $medication->increment('stock_quantity', $validated['amount']);

        ActivityLog::record(Auth::id(), 'medication_restocked', 'activity_log.medication_restocked', [
            'amount' => $validated['amount'],
            'name' => $medication->name,
        ]);

        return back()->with('success', __('admin_dashboard.flash.medication_restocked', [
            'amount' => $validated['amount'],
            'name' => $medication->name,
        ]));
    }

    /**
     * تفعيل/تعطيل دواء — بديل عن الحذف الفعلي: حذف الدواء يحذف معه (cascade)
     * كل سجلات visit_report_medications المرتبطة به، ما يمحو تاريخ الوصفات
     * الطبية من صفحة "أدويتي" للمرضى. التعطيل يخفيه من قائمة الأدوية عند
     * إرفاق تقرير جديد دون المساس بالتقارير القديمة.
     */
    public function toggleMedicationStatus(Medication $medication)
    {
        $medication->update(['is_active' => !$medication->is_active]);

        return back()->with('success', $medication->is_active
            ? __('admin_dashboard.flash.medication_activated', ['name' => $medication->name])
            : __('admin_dashboard.flash.medication_deactivated', ['name' => $medication->name]));
    }

    /**
     * صفحة الحضور: سجل يوم مختار (افتراضيًا اليوم) لكل الأطباء + المناوبون غدًا
     * + عرض (للقراءة فقط) لجدول العمل الأسبوعي. التعديل نفسه يتم من صفحة
     * تعديل حساب الدكتور.
     */
    public function attendance(Request $request)
    {
        $validated = $request->validate(['date' => 'nullable|date']);

        $selectedDate = !empty($validated['date']) ? Carbon::parse($validated['date']) : Carbon::today();
        $tomorrow = Carbon::today()->addDay();

        $doctors = User::where('role', 'doctor')
            ->with('doctorSchedule')
            ->orderBy('name')
            ->get();

        $attendanceByDoctor = DoctorAttendance::whereDate('date', $selectedDate)
            ->get()
            ->keyBy('doctor_id');

        $roster = $doctors->map(function (User $doctor) use ($selectedDate, $attendanceByDoctor) {
            $attendance = $attendanceByDoctor->get($doctor->id);

            return [
                'doctor' => $doctor,
                'scheduled' => $doctor->doctorSchedule?->isWorkingOn($selectedDate) ?? false,
                'attendance' => $attendance,
                'on_duty_now' => $selectedDate->isToday() && $attendance && !$attendance->check_out_at,
            ];
        });

        $onDutyTomorrow = $doctors->filter(
            fn (User $doctor) => $doctor->doctorSchedule?->isWorkingOn($tomorrow) ?? false
        )->values();

        return view('admin.attendance', [
            'doctors' => $doctors,
            'roster' => $roster,
            'selectedDate' => $selectedDate,
            'onDutyTomorrow' => $onDutyTomorrow,
            'tomorrow' => $tomorrow,
        ]);
    }

    /**
     * سجل نشاط مستخدم معيّن (أي دور) — تُفتح من صفحة إدارة المستخدمين
     */
    public function userActivity(User $user)
    {
        $logs = $user->activityLogs()->latest()->paginate(20);

        return view('admin.user-activity', ['targetUser' => $user, 'logs' => $logs]);
    }

    /**
     * سجل نشاط الإدارة العام — كل الأحداث المسجّلة تحت أي حساب مدير
     */
    public function activityLog()
    {
        $logs = ActivityLog::whereHas('user', fn ($q) => $q->where('role', 'admin'))
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.activity-log', compact('logs'));
    }
}