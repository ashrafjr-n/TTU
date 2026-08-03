<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DoctorAttendance;
use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DoctorController extends Controller
{
    /**
     * عرض لوحة الدكتور مع إمكانية اختيار تاريخ
     */
    public function index(Request $request)
    {
        // التاريخ المختار (افتراضيًا اليوم)، مع التحقق إنو تنسيقه صحيح
        $validated = $request->validate(['date' => 'nullable|date']);

        $date = $validated['date'] ?? null
            ? Carbon::parse($validated['date'])
            : Carbon::today();

        $bookings = Booking::with(['user', 'visitReport.medications'])
            ->where('booking_date', $date->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('booking_hour')
            ->orderBy('booking_minute')
            ->get();

        $medications = Medication::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'stock_quantity']);

        $todayAttendance = DoctorAttendance::where('doctor_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        return view('doctor.dashboard', [
            'bookings' => $bookings,
            'selectedDate' => $date,
            'medications' => $medications,
            'todayAttendance' => $todayAttendance,
        ]);
    }

    /**
     * تسجيل حضور الدكتور لليوم (صف واحد بالضبط لكل دكتور/يوم)
     */
    public function checkIn()
    {
        $attendance = DoctorAttendance::firstOrCreate(
            ['doctor_id' => Auth::id(), 'date' => Carbon::today()->toDateString()],
            ['check_in_at' => now()]
        );

        if (!$attendance->wasRecentlyCreated) {
            return back()->with('error', 'لقد سجّلت حضورك اليوم مسبقًا.');
        }

        return back()->with('success', 'تم تسجيل حضورك بنجاح.');
    }

    /**
     * تسجيل انصراف الدكتور لنفس صف الحضور اليومي
     */
    public function checkOut()
    {
        $attendance = DoctorAttendance::where('doctor_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$attendance) {
            return back()->with('error', 'يجب تسجيل الحضور أولًا.');
        }

        if ($attendance->check_out_at) {
            return back()->with('error', 'لقد سجّلت انصرافك اليوم مسبقًا.');
        }

        $attendance->update(['check_out_at' => now()]);

        return back()->with('success', 'تم تسجيل انصرافك بنجاح.');
    }

    /**
     * إلغاء حجز مؤكد (من جدول الدكتور)
     */
    public function cancelBooking(Booking $booking)
    {
        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'تم إلغاء الحجز.');
    }
}
