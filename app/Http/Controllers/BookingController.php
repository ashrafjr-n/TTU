<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $date = Carbon::today();

        // كل حجوزات اليوم المؤكدة، مفهرسة بالساعة والدقيقة لتفادي استعلامات متكررة
        $todaysBookings = Booking::where('booking_date', $date)
            ->where('status', 'confirmed')
            ->get()
            ->keyBy(fn ($b) => $b->booking_hour.':'.$b->booking_minute);

        $hours = [];
        for ($hour = Booking::OPEN_HOUR; $hour < Booking::CLOSE_HOUR; $hour++) {
            $hours[] = [
                'hour' => $hour,
                'label' => $this->formatHour($hour),
                'slots' => $this->buildSlotsForHour($date, $hour, $user, $todaysBookings),
            ];
        }

        return view('booking.index', ['hours' => $hours]);
    }

    private function buildSlotsForHour(Carbon $date, int $hour, $user, $todaysBookings): array
    {
        $minutes = $user->isStudent() ? Booking::STUDENT_MINUTES : Booking::STAFF_MINUTES;
        $slots = [];

        foreach ($minutes as $minute) {
            $slots[] = $this->describeSlot($date, $hour, $minute, $user, $todaysBookings, released: false);
        }

        // الطلاب فقط: أضف خانات الموظفين غير المحجوزة التي "تحررت" لأن وقتها بدأ فعليًا اليوم
        if ($user->isStudent()) {
            foreach (Booking::STAFF_MINUTES as $minute) {
                if ($this->isReleasedToStudents($date, $hour, $minute, $todaysBookings)) {
                    $slots[] = $this->describeSlot($date, $hour, $minute, $user, $todaysBookings, released: true);
                }
            }
        }

        return $slots;
    }

    private function describeSlot(Carbon $date, int $hour, int $minute, $user, $todaysBookings, bool $released): array
    {
        $key = "{$hour}:{$minute}";
        $existing = $todaysBookings->get($key);

        $slotStart = $date->copy()->setTime($hour, $minute, 0);
        $slotEnd = $slotStart->copy()->addMinutes(Booking::SLOT_MINUTES);
        $isPast = $released ? false : now()->gte($slotEnd);

        $isMine = $existing && $existing->user_id === $user->id;

        return [
            'hour' => $hour,
            'minute' => $minute,
            'time_label' => $this->formatHour($hour, $minute),
            'released' => $released,
            'is_past' => $isPast,
            'is_mine' => $isMine,
            'booking_id' => $isMine ? $existing->id : null,
            'is_taken' => $existing !== null && !$isMine,
        ];
    }

    /**
     * خانة موظف تتحرر للطلاب فور مرور وقت بدايتها فعليًا (اليوم فقط) بدون حجز عليها،
     * وتبقى متاحة لبقية اليوم (وليس فقط ضمن نافذة الـ5 دقائق الأصلية) كي لا تُهدر
     * السعة الشاغرة. راجع الافتراضات في تقرير التسليم.
     */
    private function isReleasedToStudents(Carbon $date, int $hour, int $minute, $todaysBookings): bool
    {
        if (!$date->isToday()) {
            return false;
        }

        $key = "{$hour}:{$minute}";
        if ($todaysBookings->has($key)) {
            return false;
        }

        $slotStart = $date->copy()->setTime($hour, $minute, 0);

        return now()->gte($slotStart);
    }

    private function formatHour(int $hour, int $minute = 0): string
    {
        $period = $hour < 12 ? 'صباحًا' : 'مساءً';
        $displayHour = $hour <= 12 ? $hour : $hour - 12;

        return sprintf('%d:%02d %s', $displayHour, $minute, $period);
    }

    public function store(Request $request)
    {
        $allMinutes = [...Booking::STUDENT_MINUTES, ...Booking::STAFF_MINUTES];

        $validated = $request->validate([
            'hour' => 'required|integer|min:'.Booking::OPEN_HOUR.'|max:'.(Booking::CLOSE_HOUR - 1),
            'minute' => ['required', 'integer', Rule::in($allMinutes)],
        ]);

        $user = Auth::user();
        $date = Carbon::today();
        $hour = (int) $validated['hour'];
        $minute = (int) $validated['minute'];
        $isStaffMinute = in_array($minute, Booking::STAFF_MINUTES, true);

        return DB::transaction(function () use ($user, $date, $hour, $minute, $isStaffMinute) {

            $slotStart = $date->copy()->setTime($hour, $minute, 0);
            $slotEnd = $slotStart->copy()->addMinutes(Booking::SLOT_MINUTES);

            if ($user->isStaff() && !$isStaffMinute) {
                return back()->with('error', 'هذا الوقت مخصص للطلاب فقط.');
            }

            if ($user->isStudent() && $isStaffMinute) {
                // مسموح فقط لو الخانة تحررت فعليًا (بدأ وقتها ولم تُحجز من موظف)
                if (now()->lt($slotStart)) {
                    return back()->with('error', 'هذا الوقت مخصص للموظفين فقط.');
                }
            } elseif (now()->gte($slotEnd)) {
                // خانات الطالب/الموظف العادية: لا حجز لوقت انتهى فعليًا
                return back()->with('error', 'انتهى وقت هذا الموعد.');
            }

            $existing = Booking::where('booking_date', $date)
                ->where('booking_hour', $hour)
                ->where('booking_minute', $minute)
                ->where('status', 'confirmed')
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                return back()->with('error', 'عذرًا، هذا الوقت تم حجزه للتو. حاول وقتًا آخر.');
            }

            $alreadyBookedThisHour = Booking::where('user_id', $user->id)
                ->where('booking_date', $date)
                ->where('booking_hour', $hour)
                ->where('status', 'confirmed')
                ->exists();

            if ($alreadyBookedThisHour) {
                return back()->with('error', 'لديك حجز مسبق ضمن هذه الساعة.');
            }

            try {
                Booking::create([
                    'user_id' => $user->id,
                    'booking_date' => $date,
                    'booking_hour' => $hour,
                    'booking_minute' => $minute,
                    'price' => Booking::PRICE,
                    'status' => 'confirmed',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // شبكة أمان أخيرة على مستوى قاعدة البيانات (active_slot_key فريد)
                // في حال تسابق تجاوز الفحص أعلاه
                return back()->with('error', 'عذرًا، هذا الوقت تم حجزه للتو. حاول وقتًا آخر.');
            }

            return back()->with('success', 'تم حجز موعدك بنجاح!');
        });
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
