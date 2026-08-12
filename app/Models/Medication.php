<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use HasFactory;

    /** رسوم كل دواء موصوف ضمن تقرير زيارة (بصرف النظر عن الكمية) */
    const PRICE_PER_ITEM = 0.20;

    protected $fillable = [
        'name_ar',
        'name_en',
        'stock_quantity',
        'low_stock_threshold',
        'unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * الاسم المعروض بلغة الواجهة الحالية — سمة افتراضية (لا عمود فعلي بقاعدة
     * البيانات) كي تبقى كل الأماكن التي تعرض/تُضمّن اسم الدواء ($m->name —
     * القوائم، مودال وصف الأدوية، الإشعارات، سجل النشاط) تعمل بلا أي تعديل،
     * وتُترجَم تلقائيًا حسب لغة كل طلب (نفس فكرة renderedDescription على
     * ActivityLog: النص يُشتق من الحالة الحالية عند القراءة، لا يُخزَّن جاهزًا).
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en,
        );
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
}
