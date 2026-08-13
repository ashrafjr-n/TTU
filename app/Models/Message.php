<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    /**
     * الحد الأقصى لطول نص أي رسالة — رسالة الطالب/الموظف عبر فورم "تواصل"
     * ورد الإدارة عليها سواء. ثابت واحد كي يبقى عدّاد الحروف بالواجهة
     * وقاعدة التحقق بالخادم مشدودين لنفس الرقم.
     */
    public const MAX_BODY_LENGTH = 700;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'parent_message_id',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * الرسالة الأصلية التي جاء هذا الرد عليها (null للرسائل الجذرية) —
     * تُستخدم لعرض سياق المحادثة داخل لوحة قراءة الرد عند المُرسِل.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    /** ردود الإدارة على هذه الرسالة، بترتيب زمني كما ظهرت. */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_message_id')->oldest();
    }
}
