<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DoctorDayAssignment extends Model
{
    /** أيام دوام العيادة القابلة للتعيين — الأحد(0) للخميس(4)، معيار Carbon::dayOfWeek */
    const CLINIC_DAYS = [0, 1, 2, 3, 4];

    protected $fillable = [
        'day_of_week',
        'doctor_id',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * الطبيب المسؤول عن تاريخ معيّن، حسب يوم أسبوعه — null لو هذا اليوم غير
     * مُعيَّن لأي طبيب حاليًا (جمعة/سبت، أو يوم عيادة بلا تعيين بعد). تُستخدم
     * لتحديد ملكية الحجز (لا عمود doctor_id على bookings) بلوحة الطبيب،
     * وبصلاحيات إلغاء الحجز/إرفاق التقرير.
     */
    public static function doctorIdForDate(Carbon $date): ?int
    {
        return static::where('day_of_week', $date->dayOfWeek)->value('doctor_id');
    }
}
