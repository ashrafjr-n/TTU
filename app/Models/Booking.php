<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    const OPEN_HOUR = 8;
    const CLOSE_HOUR = 16;

    /** الحد الأقصى لعدد الأيام المعروضة/القابلة للحجز بصفحة الحجز (قد تقل قرب نهاية الأسبوع) */
    const BOOKING_WINDOW_DAYS = 3;

    /** دقائق حصة الطلاب ضمن كل ساعة (9 خانات) */
    const STUDENT_MINUTES = [0, 5, 10, 15, 20, 25, 30, 35, 40];

    /** دقائق حصة الموظفين ضمن كل ساعة (3 خانات) */
    const STAFF_MINUTES = [45, 50, 55];

    const SLOT_MINUTES = 5;

    const PRICE = 0.25;

    /** الحد الأقصى للحجوزات المؤكدة (غير الملغاة) لكل مستخدم بالفصل الواحد */
    const SEMESTER_BOOKING_LIMIT = 4;

    protected $fillable = [
        'user_id',
        'booking_date',
        'booking_hour',
        'booking_minute',
        'price',
        'status',
        'reminder_1h_sent_at',
        'reminder_15m_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'reminder_1h_sent_at' => 'datetime',
            'reminder_15m_sent_at' => 'datetime',
        ];
    }

    /**
     * active_slot_key يُشتق تلقائيًا من الحالة + التاريخ/الساعة/الدقيقة عند كل حفظ،
     * بدل الاعتماد على كل تحكم يضبطه يدويًا. تبقى null إلا لو الحجز confirmed —
     * هذا ما يسمح بوجود unique index حقيقي على العمود رغم وجود صفوف ملغاة
     * متعددة لنفس الخانة عبر الزمن (قواعد البيانات تسمح بتكرار NULL ضمن unique).
     */
    protected static function booted(): void
    {
        static::saving(function (Booking $booking) {
            $booking->active_slot_key = $booking->status === 'confirmed'
                ? $booking->booking_date->format('Y-m-d').'|'.$booking->booking_hour.'|'.$booking->booking_minute
                : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitReport(): HasOne
    {
        return $this->hasOne(VisitReport::class);
    }

    public function isStaffMinute(): bool
    {
        return in_array($this->booking_minute, self::STAFF_MINUTES, true);
    }

    public function slotStart(): Carbon
    {
        return $this->booking_date->copy()->setTime($this->booking_hour, $this->booking_minute, 0);
    }

    public function slotEnd(): Carbon
    {
        return $this->slotStart()->addMinutes(self::SLOT_MINUTES);
    }

    /**
     * هل حان وقت الموعد فعليًا؟ تُستخدم لمنع إرفاق/تعديل تقرير زيارة قبل أن
     * يبدأ الموعد (لا معنى لتشخيص مريض قبل حضوره) — تبقى true للأبد بعد
     * بدء الموعد، فالمواعيد الماضية تبقى قابلة للتعديل دائمًا.
     */
    public function hasStarted(): bool
    {
        return now()->gte($this->slotStart());
    }

    /**
     * حجز مؤكد ولم يمر وقته بعد — هذا هو المقياس الوحيد لـ"حجز فعّال" في
     * قاعدة "حجز واحد فعّال بالمستخدم في نفس الوقت".
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'confirmed' && now()->lt($this->slotEnd());
    }

    /**
     * هل هذا الحجز لا يزال "فعّالًا" (يمنع حجزًا جديدًا) بالنسبة لهذا المستخدم؟
     *
     * حجز طالب على خانة موظف محررة استثناء مهم: isUpcoming() العادية تقارن
     * بنافذة الخانة الأصلية الضيقة (5 دقائق) — لكن هذه الخانة أساسًا لا
     * "تتحرر" للطلاب إلا بعد أن يبدأ وقتها الأصلي فعليًا (وغالبًا ينتهي خلال
     * دقائق من الحجز نفسه)، فتصبح isUpcoming() خاطئة على الفور تقريبًا رغم
     * أن الحجز جديد فعلًا. لذلك تبقى هذه الحجوزات "فعّالة" حتى نهاية يومها
     * — بمقارنة التاريخ نفسه لا بساعة إغلاق ثابتة (16:00)، لأن تلك الساعة قد
     * تكون ماضية أصلًا وقت إنشاء الحجز نفسه (حجز يُنشأ الساعة 20:00 مثلًا)
     * فتجعله "غير فعّال" فور إنشائه وتفتح ثغرة حجز مزدوج.
     */
    public function isActiveFor(User $user): bool
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        if ($user->isStudent() && $this->isStaffMinute()) {
            return $this->booking_date->gte(Carbon::today());
        }

        return $this->isUpcoming();
    }

    /**
     * حجز المستخدم الوحيد "الفعّال" حاليًا (أي تاريخ)، إن وُجد. القاعدة
     * حجز فعّال واحد فقط بالمستخدم في كل الأوقات — وليست مقيدة بتاريخ معيّن،
     * كي لا يقدر مستخدم يملك عدة حجوزات فعّالة متزامنة عبر أيام مختلفة الآن
     * بعد أن صارت صفحة الحجز تعرض أكثر من يوم. مستخدَمة من كل من
     * BookingController (بوابة /booking) ومسارات لوحات التحكم (بوابة
     * الداشبورد) لضمان نفس تعريف "الفعّال" في كلا المكانين.
     */
    public static function findActiveFor(User $user, bool $lock = false): ?self
    {
        $query = self::where('user_id', $user->id)
            ->where('status', 'confirmed')
            // لا حجز قبل اليوم يقدر يكون فعّالًا أصلًا (isActiveFor يرفضه دائمًا)،
            // فتقييد الاستعلام بهذا يمنع تحميل كل السجلات التاريخية للمستخدم
            ->where('booking_date', '>=', Carbon::today());

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->first(fn (self $b) => $b->isActiveFor($user));
    }

    /**
     * تسمية وقت الحجز المعروضة (مثال: "9:45 صباحًا") — تُستخدم في مودال
     * "لديك حجز حاليًا" أينما ظهر (لوحة التحكم أو صفحة الحجز).
     */
    public function timeLabel(): string
    {
        $hour = $this->booking_hour;
        $period = $hour < 12 ? __('common.time.am') : __('common.time.pm');
        $displayHour = $hour <= 12 ? $hour : $hour - 12;

        return sprintf('%d:%02d %s', $displayHour, $this->booking_minute, $period);
    }

    /**
     * اسم الطبيب المسؤول عن موعد هذا الحجز — يُشتق من تعيينات الأيام
     * (DoctorDayAssignment) حسب يوم أسبوع تاريخ الحجز، لأن جدول bookings لا
     * يحمل doctor_id إطلاقًا. يعود بنص بديل مترجم ("غير محدد") لو كان اليوم
     * بلا تعيين، أو كان الطبيب المُعيَّن محذوفًا — بدل اسم خاطئ أو خطأ null.
     *
     * الأسماء المخزَّنة للأطباء تتضمن سابقة "د." أصلًا، فلا تُضاف هنا ولا في
     * نصوص الترجمة كي لا تتكرر ("د. د. فلان").
     */
    public function assignedDoctorName(): string
    {
        $doctorId = DoctorDayAssignment::doctorIdForDate($this->booking_date);

        $name = $doctorId ? User::whereKey($doctorId)->value('name') : null;

        return $name ?: __('booking.active_modal.doctor_unassigned');
    }

    /**
     * بيانات حجز المستخدم "الفعّال" حاليًا (إن وُجد، أي تاريخ)، بالشكل الجاهز
     * لمودال "لديك حجز حاليًا" — تُستخدم من لوحات التحكم (لعرض المودال في
     * مكانه) ومن BookingController (بوابة /booking).
     */
    public static function activeViewDataFor(User $user): ?array
    {
        $booking = self::findActiveFor($user);

        if (!$booking) {
            return null;
        }

        return [
            'id' => $booking->id,
            'time_label' => $booking->timeLabel(),
            'date_label' => $booking->booking_date->translatedFormat('d F Y'),
            'doctor_name' => $booking->assignedDoctorName(),
        ];
    }

    /**
     * الفصل الذي يقع فيه تاريخ معيّن، بحدوده الفعلية — أو null لو التاريخ
     * يقع بين فصلين (فترة عطلة). حدود الفصول ثابتة بالكود عمدًا (غير قابلة
     * للتعديل من لوحة الإدارة)، وتواريخ خارج الفصول الثلاثة لا يُطبَّق عليها
     * حد الحجز الفصلي إطلاقًا (بدل افتراض أقرب فصل، تفاديًا لأي التباس).
     *
     * الفصل الأول يمتد عبر حدود السنة الميلادية (8 أكتوبر → 15 يناير من
     * السنة التالية)، فنبني له نافذتين محتملتين حسب سنة التاريخ المُعطى
     * (بادئة بهذه السنة، أو بادئة بالسنة الماضية) ونتحقق من كليهما — بعكس
     * الفصلين الآخرين الواقعين بالكامل ضمن سنة ميلادية واحدة.
     */
    public static function semesterFor(Carbon $date): ?array
    {
        $date = $date->copy()->startOfDay();
        $year = $date->year;

        $semester1Candidates = [
            [Carbon::create($year, 10, 8)->startOfDay(), Carbon::create($year + 1, 1, 15)->endOfDay()],
            [Carbon::create($year - 1, 10, 8)->startOfDay(), Carbon::create($year, 1, 15)->endOfDay()],
        ];

        foreach ($semester1Candidates as [$start, $end]) {
            if ($date->between($start, $end)) {
                return ['key' => 'semester_1', 'start' => $start, 'end' => $end];
            }
        }

        $semester2 = [Carbon::create($year, 2, 17)->startOfDay(), Carbon::create($year, 6, 15)->endOfDay()];
        if ($date->between($semester2[0], $semester2[1])) {
            return ['key' => 'semester_2', 'start' => $semester2[0], 'end' => $semester2[1]];
        }

        $summer = [Carbon::create($year, 7, 11)->startOfDay(), Carbon::create($year, 9, 20)->endOfDay()];
        if ($date->between($summer[0], $summer[1])) {
            return ['key' => 'summer', 'start' => $summer[0], 'end' => $summer[1]];
        }

        return null;
    }

    /**
     * عدد حجوزات المستخدم المؤكدة (غير الملغاة) بتاريخ ضمن حدود فصل معيّن —
     * الحجز الملغى لا يُحتسب إطلاقًا، فإلغاؤه يُحرر خانة من الأربع فورًا.
     */
    public static function confirmedCountInSemester(User $user, array $semester): int
    {
        return self::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->whereBetween('booking_date', [$semester['start']->toDateString(), $semester['end']->toDateString()])
            ->count();
    }

    /**
     * هل بلغ المستخدم الحد الأقصى لحجوزات الفصل الذي يقع فيه هذا التاريخ؟
     * تواريخ خارج الفصول الثلاثة (بين فصلين) بلا حد فصلي إطلاقًا — راجع
     * semesterFor().
     */
    public static function hasReachedSemesterLimit(User $user, Carbon $date): bool
    {
        $semester = self::semesterFor($date);

        if (!$semester) {
            return false;
        }

        return self::confirmedCountInSemester($user, $semester) >= self::SEMESTER_BOOKING_LIMIT;
    }

    /**
     * الأيام القابلة للحجز/العرض بصفحة الحجز: أيام دوام العيادة (الأحد–الخميس)
     * من اليوم حتى نهاية أسبوع العيادة الحالي، بحد أقصى 3 أيام — نافذة
     * BookingController حصرًا. لوحة الدكتور أسبوعية كاملة (currentWeekDates).
     *
     * النافذة لا تتخطى نهاية الأسبوع ولا تعرض يومًا مغلقًا إطلاقًا، فتصير:
     *   الأحد    → الأحد، الاثنين، الثلاثاء   (3)
     *   الاثنين  → الاثنين، الثلاثاء، الأربعاء (3)
     *   الثلاثاء → الثلاثاء، الأربعاء، الخميس (3)
     *   الأربعاء → الأربعاء، الخميس           (2)
     *   الخميس   → الخميس                     (1)
     *   الجمعة/السبت → لا شيء                 (0)
     *
     * قبل هذا كانت النافذة "اليوم + يومين" تقويميًا بلا وعي بأيام العمل، فكانت
     * تعرض الجمعة/السبت (عطلة العيادة) كأيام قابلة للحجز، وتمتد لأسبوع لاحق.
     *
     * حدّ الأسبوع هنا هو نفس عُرف السبت المستخدم بـcurrentWeekDates ولوحة
     * الدكتور (startOfWeek(Carbon::SATURDAY))، فالنظامان يتفقان على معنى "هذا
     * الأسبوع": يبدأ سبتًا وآخر أيام عمله الخميس (weekStart + 5).
     *
     * الجمعة/السبت يومان مغلقان أصلًا، فلا نافذة لهما إطلاقًا (مصفوفة فارغة)
     * بدل بدء أسبوع جديد منهما — لأن الصفحة نفسها حينها على يوم مغلق،
     * ويعرض BookingController حالة "العيادة مغلقة" بدل شبكة الأوقات.
     *
     * @return list<Carbon>
     */
    public static function bookableDates(): array
    {
        $today = Carbon::today();

        if (!in_array($today->dayOfWeek, DoctorDayAssignment::CLINIC_DAYS, true)) {
            return [];
        }

        // آخر يوم عمل بأسبوع العيادة الحالي: السبت (بداية الأسبوع) + 5 = الخميس
        $lastWorkingDay = $today->copy()->startOfWeek(Carbon::SATURDAY)->addDays(5);

        $dates = [];
        for ($date = $today->copy(); $date->lte($lastWorkingDay); $date->addDay()) {
            if (count($dates) === self::BOOKING_WINDOW_DAYS) {
                break;
            }

            // الفلتر هنا احترازي (المدى أعلاه لا يشمل جمعة/سبت أصلًا) ويجعل
            // شرط "لا يوم مغلق ضمن النافذة" صريحًا لا ضمنيًا
            if (in_array($date->dayOfWeek, DoctorDayAssignment::CLINIC_DAYS, true)) {
                $dates[] = $date->copy();
            }
        }

        return $dates;
    }

    /**
     * تسمية اليوم المعروضة فوق تبويبه بصفحة الحجز (مثال: "اليوم — 4 أغسطس").
     */
    public static function dayLabel(int $index, Carbon $date): string
    {
        $prefix = match ($index) {
            0 => __('booking.day.today'),
            1 => __('booking.day.tomorrow'),
            default => __('booking.day.day_after'),
        };

        return $prefix.' — '.$date->translatedFormat('j F');
    }

    /**
     * أيام "الأسبوع الحالي" السبعة كاملة (سبت→جمعة)، تبدأ من سبت هذا
     * الأسبوع بصرف النظر عن اليوم الفعلي الحالي — تُستخدم بلوحة الدكتور
     * (DoctorController) لعرض كل حجوزات أيامه المُعيَّنة (ماضية وقادمة) ضمن
     * الأسبوع الجاري، لا نافذة 3 أيام كصفحة الحجز.
     *
     * بداية الأسبوع سبت (لا الأحد المعتاد) لأنها أول يوم بعد إغلاق العيادة
     * الأسبوعي (خميس آخر يوم عمل، فالجمعة والسبت عطلة) — فالأسبوع "يتصفّر"
     * فعليًا مع افتتاح العيادة يوم الأحد التالي، والسبت هو أنسب حد فاصل بلا
     * لبس. startOfWeek(Carbon::SATURDAY) نفس الأسلوب المستخدم أصلًا بمخطط
     * لوحة المدير الأسبوعي (AdminController::index — startOfWeek(SUNDAY)),
     * بدل حساب الفرق يدويًا.
     */
    public static function currentWeekDates(): array
    {
        $weekStart = Carbon::today()->startOfWeek(Carbon::SATURDAY);

        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $weekStart->copy()->addDays($i);
        }

        return $dates;
    }

    /**
     * حدود أسبوع كامل (سبت → جمعة) بإزاحة عدد أسابيع للخلف: 0 = هذا الأسبوع،
     * 1 = الأسبوع الماضي، وهكذا — نفس عُرف السبت المستخدم بـcurrentWeekDates
     * وbookableDates ولوحة الدكتور، فكل ما بالتطبيق يقصد بـ"الأسبوع" الشيء
     * ذاته. يُستخدم بفلتر أسابيع سجل الحجوزات بلوحة الإدارة.
     *
     * @return array{0: Carbon, 1: Carbon} [بداية السبت، نهاية الجمعة]
     */
    public static function weekRange(int $weeksAgo): array
    {
        $start = Carbon::today()->startOfWeek(Carbon::SATURDAY)->subWeeks($weeksAgo);

        return [$start, $start->copy()->addDays(6)];
    }

    /**
     * تسمية يوم بلوحة الدكتور الأسبوعية: اسم اليوم + تاريخه (مثال: "الأحد —
     * 10 أغسطس")، مع لاحقة "(اليوم)" لو كان هو اليوم الفعلي — بعكس تسمية
     * صفحة الحجز (dayLabel) التي تفترض دائمًا يومًا ضمن نافذة صغيرة قادمة،
     * هذه تحتاج توضيح "أي يوم بالأسبوع هذا" لأن العرض يشمل أيامًا ماضية
     * وقادمة معًا.
     */
    public static function weekDayLabel(Carbon $date): string
    {
        $dayName = __('common.days')[$date->dayOfWeek];
        $label = $dayName.' — '.$date->translatedFormat('j F');

        return $date->isToday() ? $label.' ('.__('booking.day.today').')' : $label;
    }
}
