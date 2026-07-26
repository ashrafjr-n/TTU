<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorController extends Controller
{
    const STUDENT_CAPACITY = 9;
    const STAFF_CAPACITY = 3;

    /**
     * عرض لوحة الدكتور مع إمكانية اختيار تاريخ
     */
    public function index(Request $request)
    {
        // التاريخ المختار (افتراضيًا اليوم)، مع التحقق إنو تنسيقه صحيح
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $bookings = Booking::with('user')
            ->where('booking_date', $date->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('booking_hour')
            ->get();

        // الطلبات المعلقة تُعرض دايمًا بغض النظر عن التاريخ المختار
        // (لأنها بتحتاج قرار فوري بغض النظر عن أي يوم بيتصفح الدكتور)
        $pendingRequests = BookingRequest::with('user')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->get();

        return view('doctor.dashboard', [
            'bookings' => $bookings,
            'pendingRequests' => $pendingRequests,
            'selectedDate' => $date,
        ]);
    }

    /**
     * قبول طلب حجز
     */
    public function approve(BookingRequest $bookingRequest)
    {
        return DB::transaction(function () use ($bookingRequest) {

            // تأكد الطلب لسا pending (منع الضغط المزدوج على "قبول")
            $fresh = BookingRequest::lockForUpdate()->find($bookingRequest->id);

            if (!$fresh || $fresh->status !== 'pending') {
                return back()->with('error', 'هذا الطلب تم البت فيه مسبقًا.');
            }

            if ($fresh->expires_at->isPast()) {
                $fresh->update(['status' => 'expired', 'decided_at' => now()]);
                return back()->with('error', 'انتهت صلاحية هذا الطلب.');
            }

            // إعادة التحقق من السعة وقت القبول (مو وقت الطلب) - مهم!
            // لأنو ممكن تتغير الظروف بين وقت الطلب ووقت رد الدكتور
            $user = $fresh->user;
            $capacity = $user->role === 'student' ? self::STUDENT_CAPACITY : self::STAFF_CAPACITY;

            $currentCount = Booking::where('booking_date', $fresh->booking_date)
                ->where('booking_hour', $fresh->booking_hour)
                ->where('status', 'confirmed')
                ->whereHas('user', fn ($q) => $q->where('role', $user->role))
                ->lockForUpdate()
                ->count();

            if ($currentCount >= $capacity) {
                $fresh->update(['status' => 'rejected', 'decided_at' => now()]);
                return back()->with('error', 'تعذر القبول، السعة أصبحت ممتلئة.');
            }

            Booking::create([
                'user_id' => $fresh->user_id,
                'booking_date' => $fresh->booking_date,
                'booking_hour' => $fresh->booking_hour,
                'price' => 0.25,
                'status' => 'confirmed',
            ]);

            $fresh->update(['status' => 'approved', 'decided_at' => now()]);

            return back()->with('success', 'تم قبول الطلب وتأكيد الحجز.');
        });
    }

    /**
     * رفض طلب حجز
     */
    public function reject(BookingRequest $bookingRequest)
    {
        if ($bookingRequest->status !== 'pending') {
            return back()->with('error', 'هذا الطلب تم البت فيه مسبقًا.');
        }

        $bookingRequest->update([
            'status' => 'rejected',
            'decided_at' => now(),
        ]);

        return back()->with('success', 'تم رفض الطلب.');
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