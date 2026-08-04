<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\DoctorAttendance;
use App\Models\DoctorSchedule;
use App\Models\Medication;
use App\Models\UniversityRecord;
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

        $dayLabels = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

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
            'labels' => ['طلاب', 'موظفون'],
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
            $hourlyChart['labels'][] = sprintf('%d %s', $h > 12 ? $h - 12 : $h, $h < 12 ? 'ص' : 'م');
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
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('identifier', 'like', "%{$search}%");
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
            return back()->with('error', 'لا يمكن تعطيل حساب المدير.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'تفعيل' : 'تعطيل';

        ActivityLog::record(
            Auth::id(),
            $user->is_active ? 'user_activated' : 'user_deactivated',
            "{$status} حساب \"{$user->name}\""
        );

        return back()->with('success', "تم {$status} حساب {$user->name}.");
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
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Password::defaults()],
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|min:0|max:6',
        ]);

        $doctor = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'doctor',
            'identifier' => $validated['email'],
        ]);

        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'working_days' => $this->normalizeWorkingDays($validated['working_days'] ?? []),
        ]);

        ActivityLog::record(Auth::id(), 'doctor_created', "إنشاء حساب دكتور: {$validated['name']} ({$validated['email']})");

        return redirect()->route('admin.users')->with('success', 'تم إضافة حساب الدكتور بنجاح.');
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor->id)],
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|min:0|max:6',
        ]);

        $doctor->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            // identifier للأطباء هو بريدهم (لا يوجد رقم جامعي/وظيفي لهم) —
            // يجب أن يتبع البريد وإلا صار تسجيل الدخول بالـidentifier القديم
            'identifier' => $validated['email'],
        ]);

        DoctorSchedule::updateOrCreate(
            ['doctor_id' => $doctor->id],
            ['working_days' => $this->normalizeWorkingDays($validated['working_days'] ?? [])]
        );

        ActivityLog::record(Auth::id(), 'doctor_updated', "تحديث حساب دكتور: \"{$doctor->name}\"");

        return redirect()->route('admin.users')->with('success', "تم تحديث حساب \"{$doctor->name}\".");
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
     * عرض سجلات الجامعة (university_records)
     */
    public function records(Request $request)
    {
        $query = UniversityRecord::query();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $records = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.records', compact('records'));
    }

    /**
     * إضافة سجل جديد (رقم جامعي/وظيفي صحيح)
     */
    public function storeRecord(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'unique:university_records,identifier'],
            'type' => ['required', 'in:student,staff'],
        ]);

        UniversityRecord::create([
            'identifier' => $validated['identifier'],
            'type' => $validated['type'],
            'is_valid' => true,
        ]);

        $typeLabel = $validated['type'] === 'student' ? 'طالب' : 'موظف';
        ActivityLog::record(Auth::id(), 'university_record_added', "إضافة رقم {$validated['identifier']} ({$typeLabel})");

        return back()->with('success', 'تمت إضافة الرقم بنجاح.');
    }

    /**
     * حذف سجل (إبطال رقم)
     */
    public function destroyRecord(UniversityRecord $record)
    {
        $identifier = $record->identifier;
        $record->delete();

        ActivityLog::record(Auth::id(), 'university_record_removed', "حذف رقم {$identifier}");

        return back()->with('success', 'تم حذف السجل بنجاح.');
    }

    /**
     * عرض كتالوج الأدوية
     */
    public function medications()
    {
        $medications = Medication::orderBy('name')->paginate(15);

        return view('admin.medications', compact('medications'));
    }

    /**
     * إضافة دواء جديد للكتالوج
     */
    public function storeMedication(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:medications,name',
            'stock_quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        Medication::create($validated);

        ActivityLog::record(Auth::id(), 'medication_added', "إضافة دواء: {$validated['name']}");

        return back()->with('success', 'تمت إضافة الدواء بنجاح.');
    }

    /**
     * تعديل بيانات دواء (الاسم/الوحدة/حد التنبيه) — لا يغيّر الكمية،
     * فذلك مسؤولية "إضافة كمية" حصرًا
     */
    public function updateMedication(Request $request, Medication $medication)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('medications', 'name')->ignore($medication->id)],
            'unit' => 'nullable|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        $medication->update($validated);

        ActivityLog::record(Auth::id(), 'medication_edited', "تعديل بيانات دواء: {$medication->name}");

        return back()->with('success', 'تم تحديث بيانات الدواء بنجاح.');
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

        ActivityLog::record(Auth::id(), 'medication_restocked', "إضافة {$validated['amount']} إلى مخزون \"{$medication->name}\"");

        return back()->with('success', "تمت إضافة {$validated['amount']} إلى مخزون \"{$medication->name}\".");
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

        $status = $medication->is_active ? 'تفعيل' : 'تعطيل';
        return back()->with('success', "تم {$status} \"{$medication->name}\".");
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