<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * تسجيل حدث نشاط — واجهة موحّدة لكل نقاط التسجيل بالتطبيق
     */
    public static function record(int $userId, string $action, ?string $description = null): self
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
