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
     * تسجيل حدث نشاط — واجهة موحّدة لكل نقاط التسجيل بالتطبيق. يُخزَّن الوصف
     * كمفتاح ترجمة + معطياته (لا كنص جاهز) كي يُترجَم عند العرض بلغة
     * المستخدم الحالية، لا باللغة التي كانت مفعّلة وقت وقوع الحدث.
     */
    public static function record(int $userId, string $action, ?string $descriptionKey = null, array $params = []): self
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $descriptionKey ? json_encode(['key' => $descriptionKey, 'params' => $params]) : null,
        ]);
    }

    public function renderedDescription(): ?string
    {
        if (! $this->description) {
            return null;
        }

        $decoded = json_decode($this->description, true);

        // سجلات قديمة قد تحمل نصًا جاهزًا مباشرة (قبل اعتماد مفتاح+معطيات)
        if (! is_array($decoded) || ! isset($decoded['key'])) {
            return $this->description;
        }

        return __($decoded['key'], $decoded['params'] ?? []);
    }
}
