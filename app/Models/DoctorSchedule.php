<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'working_days',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'array',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * working_days تخزن أرقام أيام الأسبوع بمعيار Carbon::dayOfWeek
     * (0 = الأحد ... 6 = السبت).
     */
    public function isWorkingOn(Carbon $date): bool
    {
        return in_array($date->dayOfWeek, $this->working_days ?? [], true);
    }
}
