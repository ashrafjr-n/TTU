<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * عدّاد طلبات مساعد الدعم اليومي — صف واحد لكل تاريخ، فيتصفّر ضمنيًا بتغيّر
 * اليوم بدل الاعتماد على مهمة مجدولة (راجع migration create_chatbot_usage_table).
 */
class ChatbotUsage extends Model
{
    protected $table = 'chatbot_usage';

    protected $fillable = ['usage_date', 'requests_count'];

    /**
     * usage_date يبقى بلا cast تاريخ عن قصد: مع الـcast يكتب Eloquent القيمة
     * بصيغة 'Y-m-d H:i:s'، فتُخزَّن في SQLite (بيئة الاختبارات) كنص بجزء وقت
     * لا يطابق مقارنة 'Y-m-d'، بينما تقصّه Postgres لعمود date. التعامل معه
     * كسلسلة 'Y-m-d' في الطرفين يجعل السلوك واحدًا في البيئتين.
     */
    public static function dateKey(): string
    {
        return today()->toDateString();
    }

    /**
     * عدد الطلبات المستهلكة اليوم.
     */
    public static function usedToday(): int
    {
        return (int) (self::where('usage_date', self::dateKey())->value('requests_count') ?? 0);
    }

    /**
     * حجز حصة واحدة من سقف اليوم قبل أي استدعاء للـAPI.
     *
     * يُحجز مسبقًا (لا بعد نجاح الاستدعاء) لأن الطلب الفاشل أو المنقطع يستهلك
     * حصة عند المزوّد أيضًا؛ الأمان هنا في اتجاه حماية الحصة المجانية.
     *
     * @return bool false لو نفد سقف اليوم — على المستدعي أن يعرض الرسالة الثابتة
     */
    public static function reserveSlot(int $dailyLimit): bool
    {
        if ($dailyLimit <= 0) {
            return false;
        }

        return DB::transaction(function () use ($dailyLimit) {
            $date = self::dateKey();

            // القفل يمنع طلبين متزامنين من قراءة نفس العدّاد ثم زيادته معًا،
            // فيتجاوز الاستهلاك الفعلي السقف بمقدار عدد الطلبات المتزامنة.
            $row = self::where('usage_date', $date)->lockForUpdate()->first();

            if (! $row) {
                try {
                    $row = self::create(['usage_date' => $date, 'requests_count' => 0]);
                } catch (QueryException) {
                    // سبقنا طلب آخر لإنشاء صف اليوم (usage_date فريد) — نقرأه بقفل
                    $row = self::where('usage_date', $date)->lockForUpdate()->first();

                    if (! $row) {
                        return false;
                    }
                }
            }

            if ($row->requests_count >= $dailyLimit) {
                return false;
            }

            $row->increment('requests_count');

            return true;
        });
    }
}
