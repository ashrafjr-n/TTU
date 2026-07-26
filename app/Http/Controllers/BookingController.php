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
    // الإعدادات الثابتة للنظام
    const OPEN_HOUR = 8;
    const CLOSE_HOUR = 16;
    const STUDENT_CAPACITY = 9;
    const STAFF_CAPACITY = 3;
    const REQUEST_EXPIRY_MINUTES = 15;
    const PRICE = 0.25;

    /**
     * عرض صفحة الحجز مع حساب حالة كل ساعة
     */
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

    /**
     * حساب حالة ساعة معينة (كم محجوز، شو الخيار المتاح للمستخدم الحالي)
     */
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

        // هل المستخدم الحالي عندو حجز أو طلب موجود مسبقًا بهاي الساعة؟
        $alreadyBooked = Booking::where('user_id', $user->id)
            ->where('booking_date', $date)
            ->where('booking_hour', $hour)
            ->where('status', 'confirmed')
            ->exists();

        $pendingRequest = BookingRequest::where('user_id', $user->id)
            ->where('booking_date', $date)
            ->where('booking_hour', $hour)
            ->where('status', 'pending')
            ->exists();

        // تحديد الحالة حسب دور المستخدم (طالب أو موظف)
        if ($user->isStudent()) {
            $ownFull = $studentFull;
            $otherFull = $staffFull;
        } else { // staff
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
            'already_booked' => $alreadyBooked,
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

    /**
     * حجز مباشر (لما فيه مجال ضمن الحصة العادية)
     */
    public function store(Request $request)
    {
        $request->validate([
            'hour' => 'required|integer|min:8|max:15',
        ]);

        $user = Auth::user();
        $date = Carbon::today();
        $hour = $request->input('hour');

        // نستخدم Transaction + Lock عشان نمنع تعارض الحجز
        // (لو شخصين ضغطو "احجز" بنفس اللحظة تمامًا على آخر مقعد فاضي)
        return DB::transaction(function () use ($user, $date, $hour) {

            // نقفل الصفوف المتعلقة بهاي الساعة أثناء الفحص والحجز
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

            // تحقق إنو المستخدم ما عندوش حجز مسبق بنفس الساعة
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

    /**
     * إرسال طلب حجز (لما الحصة الخاصة فيك مليانة، بس حصة الفئة التانية فيها مجال)
     */
    public function requestBooking(Request $request)
    {
        $request->validate([
            'hour' => 'required|integer|min:8|max:15',
        ]);

        $user = Auth::user();
        $date = Carbon::today();
        $hour = $request->input('hour');

        // تحقق إنو ما في طلب pending مسبق لنفس المستخدم بنفس الساعة
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
}