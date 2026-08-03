<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VisitReportMedication extends Pivot
{
    protected $table = 'visit_report_medications';

    public $incrementing = true;

    protected $fillable = [
        'visit_report_id',
        'medication_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
