<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VisitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'doctor_id',
        'condition',
        'examination',
        'diagnosis',
        'treatment_plan',
        'notes',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function medications(): BelongsToMany
    {
        return $this->belongsToMany(Medication::class, 'visit_report_medications')
            ->using(VisitReportMedication::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * رسوم هذا التقرير: 0.20 د.أ لكل دواء موصوف (بصرف النظر عن الكمية) —
     * تفترض أن medications محمَّلة مسبقًا (eager load) لتفادي استعلام إضافي.
     */
    public function medicationsFee(): float
    {
        return $this->medications->count() * Medication::PRICE_PER_ITEM;
    }
}
