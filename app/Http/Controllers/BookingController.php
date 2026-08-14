<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $activeBooking = $this->findActiveBooking($user);

        if ($activeBooking) {
            return view('booking.index', [
                'days' => [],
                'activeBooking' => Booking::activeViewDataFor($user),
                'semesterLimitReached' => false,
                'clinicClosed' => false,
            ]);
        }

        $bookableDates = Booking::bookableDates();

        // نافذة الحجز مغلقة الآن (من الخميس 4 عصرًا حتى نهاية الجمعة —
        // Booking::isBookingWindowClosed()) فلا أيام قابلة للحجز إطلاقًا. لا
        // يشمل هذا السبت: bookableDates() تعيد له الأحد والاثنين القادمين
        // خصيصًا، فمصفوفة فارغة هنا تعني الإغلاق الفعلي حصرًا. الفحص قبل فحص
        // الحد الفصلي أدناه عمدًا: every() على مجموعة فارغة تعود true، فبدون
        // هذا الترتيب كانت الصفحة ستعرض "بلغت الحد الأقصى للفصل" وهي رسالة
        // خاطئة تمامًا في فترة إغلاق.
        if (empty($bookableDates)) {
            return view('booking.index', [
                'days' => [],
                'activeBooking' => null,
                'semesterLimitReached' => false,
                'clinicClosed' => true,
                'clinicClosedMessage' => Booking::closedWindowDescription(),
            ]);
        }

        // الحد الفصلي (4 حجوزات مؤكدة كحد أقصى) — نافذة الحجز المعروضة قد
        // تعبر حدود فصلين قرب نهاية/بداية فصل، فنخفي الصفحة كليًا فقط لو كانت
        // كل أيام النافذة محظورة فعلًا؛ لو يوم واحد منها لا يزال مسموحًا (فصل
        // مختلف، أو فجوة بلا فصل نشط) تُعرض شبكة الأوقات كالمعتاد، ويبقى
        // store() هو الفيصل الفعلي لكل تاريخ على حدة.
        if (collect($bookableDates)->every(fn (Carbon $date) => Booking::hasReachedSemesterLimit($user, $date))) {
            return view('booking.index', [
                'days' => [],
                'activeBooking' => null,
                'semesterLimitReached' => true,
                'clinicClosed' => false,
            ]);
        }

        $days = [];
        foreach ($bookableDates as $index => $date) {
            // كل حجوزات هذا اليوم المؤكدة، مفهرسة بالساعة والدقيقة لتفادي استعلامات متكررة
            $dayBookings = Booking::where('booking_date', $date)
                ->where('status', 'confirmed')
                ->get()
                ->keyBy(fn ($b) => $b->booking_hour.':'.$b->booking_minute);

            $hours = [];
            for ($hour = Booking::OPEN_HOUR; $hour < Booking::CLOSE_HOUR; $hour++) {
                $hours[] = [
                    'hour' => $hour,
                    'label' => $this->formatHour($hour),
                    'slots' => $this->buildSlotsForHour($date, $hour, $user, $dayBookings),
                ];
            }

            $days[] = [
                'index' => $index,
                'date' => $date->toDateString(),
                'label' => Booking::dayLabel($date),
                'hours' => $hours,
                'default_hour' => $this->defaultHour($hours),
            ];
        }

        return view('booking.index', [
            'days' => $days,
            'activeBooking' => null,
            'semesterLimitReached' => false,
            'clinicClosed' => false,
        ]);
    }

    /**
     * الساعة المختارة افتراضيًا عند فتح يوم معيّن: أول ساعة فيها خانة (من خانات
     * الدور الأساسية، دون احتساب خانات الموظفين المحرَّرة للطلاب لأنها تبقى
     * متاحة طوال اليوم ولا تعكس "الآن") لم يفت وقتها بعد — أي أقرب ساعة
     * قادمة/جارية بهذا اليوم. لو كل الخانات انتهى وقتها (أو اليوم مستقبلي
     * فكل خاناته "قادمة" أصلًا)، تُختار أول/آخر ساعة على التوالي.
     */
    private function defaultHour(array $hours): ?int
    {
        foreach ($hours as $hourBlock) {
            $hasUpcoming = collect($hourBlock['slots'])
                ->reject(fn ($slot) => $slot['released'])
                ->contains(fn ($slot) => !$slot['is_past']);

            if ($hasUpcoming) {
                return $hourBlock['hour'];
            }
        }

        return empty($hours) ? null : end($hours)['hour'];
    }

    /**
     * حجز المستخدم الوحيد "الفعّال" حاليًا، إن وُجد (أي تاريخ ضمن نافذة الحجز).
     * يُستخدم لفرض قاعدة "حجز فعّال واحد بالمستخدم بأي وقت".
     *
     * lockForUpdate عند $lock=true (داخل transaction الحجز) — بدونه، طلبان
     * متزامنان لنفس المستخدم (تبويبان، أو نقرة مزدوجة) لخانتين مختلفتين
     * يقدران يقرآ "لا يوجد حجز فعّال" معًا قبل أي كومِت، فينجح كلاهما.
     */
    private function findActiveBooking($user, bool $lock = false): ?Booking
    {
        return Booking::findActiveFor($user, $lock);
    }

    private function buildSlotsForHour(Carbon $date, int $hour, $user, $dayBookings): array
    {
        $minutes = $user->isStudent() ? Booking::STUDENT_MINUTES : Booking::STAFF_MINUTES;
        $slots = [];

        foreach ($minutes as $minute) {
            $slots[] = $this->describeSlot($date, $hour, $minute, $dayBookings, released: false);
        }

        // الطلاب فقط: أضف خانات الموظفين غير المحجوزة التي "تحررت" لأن وقتها بدأ فعليًا اليوم
        if ($user->isStudent()) {
            foreach (Booking::STAFF_MINUTES as $minute) {
                if ($this->isReleasedToStudents($date, $hour, $minute, $dayBookings)) {
                    $slots[] = $this->describeSlot($date, $hour, $minute, $dayBookings, released: true);
                }
            }
        }

        return $slots;
    }

    /**
     * @param  \Illuminate\Support\Collection  $dayBookings
     *
     * ملاحظة: هذه الدالة تُستدعى فقط عندما لا يوجد للمستخدم أي حجز فعّال
     * (راجع index())، لذلك أي خانة ضمن هذه القائمة تخص المستخدم نفسه تكون
     * بالضرورة حجزًا انتهى وقته فعليًا — تُعرض كـ"منتهية" مثل أي خانة أخرى
     * بدل عرضها كحجز قابل للإلغاء (فلا معنى لإلغاء موعد مضى وقته).
     */
    private function describeSlot(Carbon $date, int $hour, int $minute, $dayBookings, bool $released): array
    {
        $key = "{$hour}:{$minute}";
        $existing = $dayBookings->get($key);

        $slotStart = $date->copy()->setTime($hour, $minute, 0);
        $slotEnd = $slotStart->copy()->addMinutes(Booking::SLOT_MINUTES);
        $isPast = $released ? false : now()->gte($slotEnd);

        return [
            'hour' => $hour,
            'minute' => $minute,
            'time_label' => $this->formatHour($hour, $minute),
            'released' => $released,
            'is_past' => $isPast,
            'is_taken' => $existing !== null,
        ];
    }

    /**
     * خانة موظف تتحرر للطلاب فور مرور وقت بدايتها فعليًا (اليوم فقط) بدون حجز عليها،
     * وتبقى متاحة لبقية اليوم (وليس فقط ضمن نافذة الـ5 دقائق الأصلية) كي لا تُهدر
     * السعة الشاغرة. الأيام القادمة (غدًا/بعد الغد) لا تُطبَّق عليها آلية التحرر
     * أبدًا — تبقى خانات الموظفين حكرًا عليهم حتى يصير ذلك اليوم "اليوم" فعليًا.
     */
    private function isReleasedToStudents(Carbon $date, int $hour, int $minute, $dayBookings): bool
    {
        if (!$date->isToday()) {
            return false;
        }

        $key = "{$hour}:{$minute}";
        if ($dayBookings->has($key)) {
            return false;
        }

        $slotStart = $date->copy()->setTime($hour, $minute, 0);

        return now()->gte($slotStart);
    }

    private function formatHour(int $hour, int $minute = 0): string
    {
        $period = $hour < 12 ? __('common.time.am') : __('common.time.pm');
        $displayHour = $hour <= 12 ? $hour : $hour - 12;

        return sprintf('%d:%02d %s', $displayHour, $minute, $period);
    }

    public function store(Request $request)
    {
        $allMinutes = [...Booking::STUDENT_MINUTES, ...Booking::STAFF_MINUTES];
        $bookableDates = collect(Booking::bookableDates())->map->toDateString()->all();

        $validated = $request->validate([
            // اختياري: لو غاب، نفترض اليوم (توافقًا مع أي مستدعٍ لا يرسل تاريخًا)
            'date' => ['nullable', 'date_format:Y-m-d', Rule::in($bookableDates)],
            'hour' => 'required|integer|min:'.Booking::OPEN_HOUR.'|max:'.(Booking::CLOSE_HOUR - 1),
            'minute' => ['required', 'integer', Rule::in($allMinutes)],
        ]);

        $user = Auth::user();
        $date = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
            : Carbon::today();

        // التاريخ المُرسل تحققت منه Rule::in أعلاه، أما التاريخ الافتراضي (طلب
        // بلا 'date') فلا يمر بها — ولولا هذا الفحص لأمكن حجز "اليوم" في يوم
        // عطلة (جمعة/سبت) حيث النافذة فارغة أصلًا.
        if (!in_array($date->toDateString(), $bookableDates, true)) {
            return back()->with('error', __('booking.errors.clinic_closed'));
        }

        $hour = (int) $validated['hour'];
        $minute = (int) $validated['minute'];
        $isStaffMinute = in_array($minute, Booking::STAFF_MINUTES, true);

        return DB::transaction(function () use ($user, $date, $hour, $minute, $isStaffMinute) {

            $slotStart = $date->copy()->setTime($hour, $minute, 0);
            $slotEnd = $slotStart->copy()->addMinutes(Booking::SLOT_MINUTES);

            if ($user->isStaff() && !$isStaffMinute) {
                return back()->with('error', __('booking.errors.students_only'));
            }

            if ($user->isStudent() && $isStaffMinute) {
                // مسموح فقط لو الخانة تحررت فعليًا (بدأ وقتها ولم تُحجز من موظف)
                if (now()->lt($slotStart)) {
                    return back()->with('error', __('booking.errors.staff_only'));
                }

                // حد أعلى: لا حجز لخانة محررة بعد ساعة إغلاق العيادة (16:00) —
                // بدون هذا الحد يقدر طالب يحجز خانة "محررة" في ساعة متأخرة من
                // الليل طالما بدأ وقتها الأصلي اليوم فعليًا.
                if (now()->gte($date->copy()->setTime(Booking::CLOSE_HOUR, 0))) {
                    return back()->with('error', __('booking.errors.released_slot_closed'));
                }
            } elseif (now()->gte($slotEnd)) {
                // خانات الطالب/الموظف العادية: لا حجز لوقت انتهى فعليًا
                return back()->with('error', __('booking.errors.slot_expired'));
            }

            // قفل صف المستخدم نفسه أولًا (موجود دائمًا، بعكس صفوف الحجوزات) لضمان
            // تسلسل أي طلبات متزامنة لنفس المستخدم — بدونه، طلبان متزامنان من
            // مستخدم بلا أي حجز حاليًا يقدران يقرآ "لا يوجد حجز فعّال" معًا قبل
            // أي كومِت، فينجح كلاهما على خانتين مختلفتين (نفس ثغرة الحجز المكرر
            // الأصلية، لكن على مستوى المستخدم بدل الخانة).
            User::where('id', $user->id)->lockForUpdate()->first();

            // قاعدة: حجز فعّال واحد فقط بالمستخدم بأي وقت (أي يوم ضمن نافذة
            // الحجز) — يجب إلغاء الحجز الحالي أولًا قبل حجز موعد جديد.
            if ($this->findActiveBooking($user, lock: true)) {
                return back()->with('error', __('booking.errors.already_have_active_booking'));
            }

            // قاعدة إضافية (وليست بديلة عن السابقة): 4 حجوزات مؤكدة كحد أقصى
            // بالفصل الواحد — على تاريخ الحجز الفعلي نفسه، لا "اليوم"، كي
            // تبقى صحيحة حتى لو نافذة الحجز المعروضة عبرت حدود فصلين.
            if (Booking::hasReachedSemesterLimit($user, $date)) {
                return back()->with('error', __('booking.errors.semester_limit_reached'));
            }

            $existing = Booking::where('booking_date', $date)
                ->where('booking_hour', $hour)
                ->where('booking_minute', $minute)
                ->where('status', 'confirmed')
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                return back()->with('error', __('booking.errors.just_taken'));
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
            } catch (QueryException) {
                // شبكة أمان أخيرة على مستوى قاعدة البيانات (active_slot_key فريد)
                // في حال تسابق تجاوز الفحص أعلاه
                return back()->with('error', __('booking.errors.just_taken'));
            }

            ActivityLog::record(
                $user->id,
                'booking_created',
                'activity_log.booking_created',
                ['date' => $date->toDateString(), 'time' => $this->formatHour($hour, $minute)]
            );

            // إشعار التأكيد يُمرَّر بمفتاح 'toast' المستقل (يعرضه <x-flash-toast/>
            // بالتخطيط العام) لا بمفتاح 'success' وحده، لأن الوجهة هنا هي لوحة
            // المستخدم وهي لا تعرض شريط 'success' الداخلي أصلًا — فبدونه كان
            // الحجز ينجح بلا أي تأكيد مرئي. يبقى 'success' مضبوطًا كما كان لأي
            // صفحة تعرضه (مثل صفحة الحجز نفسها).
            return redirect()->route('dashboard.'.$user->role)
                ->with('success', __('booking.flash.booked_success'))
                ->with('toast', [
                    'type' => 'success',
                    'title' => __('booking.toast.title'),
                    'message' => __('booking.flash.booked_success_toast', [
                        'date' => $date->translatedFormat('d F Y'),
                        'time' => $this->formatHour($hour, $minute),
                    ]),
                ]);
        });
    }

    /**
     * إلغاء حجز — يقتصر على صاحب الحجز نفسه
     */
    public function destroy(Booking $booking)
    {
        // حماية: لا يقدر أي مستخدم يلغي حجز شخص تاني عن طريق تغيير الرقم بالرابط يدويًا
        if ($booking->user_id !== Auth::id()) {
            abort(403, __('booking.errors.not_authorized_to_cancel'));
        }

        if ($booking->status !== 'confirmed') {
            return redirect()->route('booking.index')->with('error', __('booking.errors.not_cancellable'));
        }

        $booking->update(['status' => 'cancelled']);

        ActivityLog::record(
            $booking->user_id,
            'booking_cancelled',
            'activity_log.booking_cancelled',
            ['date' => $booking->booking_date->toDateString(), 'time' => $this->formatHour($booking->booking_hour, $booking->booking_minute)]
        );

        return redirect()->route('booking.index')->with('success', __('booking.flash.cancelled_success'));
    }
}
