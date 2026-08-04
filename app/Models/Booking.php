<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    const OPEN_HOUR = 8;
    const CLOSE_HOUR = 16;

    /** عدد الأيام المعروضة/القابلة للحجز في صفحة الحجز: اليوم + يومين قادمين */
    const BOOKING_WINDOW_DAYS = 3;

    /** دقائق حصة الطلاب ضمن كل ساعة (9 خانات) */
    const STUDENT_MINUTES = [0, 5, 10, 15, 20, 25, 30, 35, 40];

    /** دقائق حصة الموظفين ضمن كل ساعة (3 خانات) */
    const STAFF_MINUTES = [45, 50, 55];

    const SLOT_MINUTES = 5;

    const PRICE = 0.25;

    protected $fillable = [
        'user_id',
        'booking_date',
        'booking_hour',
        'booking_minute',
        'price',
        'status',
        'reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'reminded_at' => 'datetime',
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

    public function slotStart(): \Illuminate\Support\Carbon
    {
        return $this->booking_date->copy()->setTime($this->booking_hour, $this->booking_minute, 0);
    }

    public function slotEnd(): \Illuminate\Support\Carbon
    {
        return $this->slotStart()->addMinutes(self::SLOT_MINUTES);
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
            return $this->booking_date->gte(\Illuminate\Support\Carbon::today());
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
            ->where('booking_date', '>=', \Illuminate\Support\Carbon::today());

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
        ];
    }
}
