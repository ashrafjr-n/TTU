<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniversityRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'type',
        'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
        ];
    }
}