<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Medication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'stock_quantity',
        'low_stock_threshold',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function visitReports(): BelongsToMany
    {
        return $this->belongsToMany(VisitReport::class, 'visit_report_medications')
            ->using(VisitReportMedication::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
}
