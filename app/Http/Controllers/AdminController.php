<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Medication;
use App\Models\UniversityRecord;
use App\Models\User;
use Illuminate\Http\Request;
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

        return view('admin.dashboard', compact('stats'));
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
     * حفظ دكتور جديد
     */
    public function storeDoctor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'doctor',
            'identifier' => $validated['email'],
        ]);

        return redirect()->route('admin.users')->with('success', 'تم إضافة حساب الدكتور بنجاح.');
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

        return back()->with('success', 'تمت إضافة الرقم بنجاح.');
    }

    /**
     * حذف سجل (إبطال رقم)
     */
    public function destroyRecord(UniversityRecord $record)
    {
        $record->delete();

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
}