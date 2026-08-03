<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Medication;
use Illuminate\Http\Request;
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

        return view('doctor.dashboard', [
            'bookings' => $bookings,
            'selectedDate' => $date,
            'medications' => $medications,
        ]);
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
