<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    const OPEN_HOUR = 8;
    const CLOSE_HOUR = 16;

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
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
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

    public function isStaffMinute(): bool
    {
        return in_array($this->booking_minute, self::STAFF_MINUTES, true);
    }
}
