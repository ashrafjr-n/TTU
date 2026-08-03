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
}
