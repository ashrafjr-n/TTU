<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    const OPEN_HOUR = 8;
    const CLOSE_HOUR = 16;
    const STUDENT_CAPACITY = 9;
    const STAFF_CAPACITY = 3;
    const REQUEST_EXPIRY_MINUTES = 15;
    const PRICE = 0.25;

    public function index()
    {
        $user = Auth::user();
        $date = Carbon::today();

        $slots = [];
        for ($hour = self::OPEN_HOUR; $hour < self::CLOSE_HOUR; $hour++) {
            $slots[] = $this->getSlotStatus($date, $hour, $user);
        }

        return view('booking.index', ['slots' => $slots]);
    }

    private function getSlotStatus(Carbon $date, int $hour, $user): array
    {
        $studentBooked = Booking::where('booking_date', $date)
            ->where('booking_hour', $hour)
            ->where('status', 'confirmed')
            ->whereHas('user', fn ($q) => $q->where('role', 'student'))
            ->count();

        $staffBooked = Booking::where('booking_date', $date)
            ->where('booking_hour', $hour)
            ->where('status', 'confirmed')
            ->whereHas('user', fn ($q) => $q->where('role', 'staff'))
            ->count();

        $studentFull = $studentBooked >= self::STUDENT_CAPACITY;
        $staffFull = $staffBooked >= self::STAFF_CAPACITY;

        // بدل exists()، منجيب الحجز نفسه عشان نقدر نلغيه لاحقًا (لو موجود)
        $myBooking = Booking::where('user_id', $user->id)
            ->where('booking_date', $date)
            ->where('booking_hour', $hour)
            ->where('status', 'confirmed')
            ->first();

        $pendingRequest = BookingRequest::where('user_id', $user->id)
            ->where('booking_date', $date)
            ->where('booking_hour', $hour)
            ->where('status', 'pending')
            ->exists();

        if ($user->isStudent()) {
            $ownFull = $studentFull;
            $otherFull = $staffFull;
        } else {
            $ownFull = $staffFull;
            $otherFull = $studentFull;
        }

        $canBookDirectly = !$ownFull;
        $canRequest = $ownFull && !$otherFull;

        return [
            'hour' => $hour,
            'time_label' => $this->formatHour($hour),
            'student_booked' => $studentBooked,
            'student_capacity' => self::STUDENT_CAPACITY,
            'staff_booked' => $staffBooked,
            'staff_capacity' => self::STAFF_CAPACITY,
            'already_booked' => $myBooking !== null,
            'my_booking_id' => $myBooking?->id,
            'pending_request' => $pendingRequest,
            'can_book_directly' => $canBookDirectly,
            'can_request' => $canRequest,
        ];
    }

    private function formatHour(int $hour): string
    {
        $period = $hour < 12 ? 'صباحًا' : 'مساءً';
        $displayHour = $hour <= 12 ? $hour : $hour - 12;
        return "{$displayHour}:00 {$period}";
    }

    public function store(Request $request)
    {
        $request->validate([
            'hour' => 'required|integer|min:8|max:15',
        ]);

        $user = Auth::user();
        $date = Carbon::today();
        $hour = $request->input('hour');

        return DB::transaction(function () use ($user, $date, $hour) {

            $capacity = $user->isStudent() ? self::STUDENT_CAPACITY : self::STAFF_CAPACITY;

            $currentCount = Booking::where('booking_date', $date)
                ->where('booking_hour', $hour)
                ->where('status', 'confirmed')
                ->whereHas('user', fn ($q) => $q->where('role', $user->role))
                ->lockForUpdate()
                ->count();

            if ($currentCount >= $capacity) {
                return back()->with('error', 'عذرًا، هذا الوقت أصبح محجوزًا بالكامل. حاول وقتًا آخر.');
            }

            $alreadyBooked = Booking::where('user_id', $user->id)
                ->where('booking_date', $date)
                ->where('booking_hour', $hour)
                ->where('status', 'confirmed')
                ->exists();

            if ($alreadyBooked) {
                return back()->with('error', 'لديك حجز مسبق بهذا الوقت.');
            }

            Booking::create([
                'user_id' => $user->id,
                'booking_date' => $date,
                'booking_hour' => $hour,
                'price' => self::PRICE,
                'status' => 'confirmed',
            ]);

            return back()->with('success', 'تم حجز موعدك بنجاح!');
        });
    }

    public function requestBooking(Request $request)
    {
        $request->validate([
            'hour' => 'required|integer|min:8|max:15',
        ]);

        $user = Auth::user();
        $date = Carbon::today();
        $hour = $request->input('hour');

        $existingRequest = BookingRequest::where('user_id', $user->id)
            ->where('booking_date', $date)
            ->where('booking_hour', $hour)
            ->where('status', 'pending')
            ->exists();

        if ($existingRequest) {
            return back()->with('error', 'لديك طلب حجز معلق مسبقًا لهذا الوقت.');
        }

        BookingRequest::create([
            'user_id' => $user->id,
            'booking_date' => $date,
            'booking_hour' => $hour,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(self::REQUEST_EXPIRY_MINUTES),
        ]);

        return back()->with('success', 'تم إرسال طلبك، بانتظار موافقة الدكتور.');
    }

    /**
     * إلغاء حجز — يقتصر على صاحب الحجز نفسه
     */
    public function destroy(Booking $booking)
    {
        // حماية: لا يقدر أي مستخدم يلغي حجز شخص تاني عن طريق تغيير الرقم بالرابط يدويًا
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'ليس لديك صلاحية إلغاء هذا الحجز.');
        }

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'هذا الحجز غير قابل للإلغاء.');
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'تم إلغاء حجزك بنجاح.');
    }
}