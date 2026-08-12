<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * تنظيف لمرة واحدة لتعارض المعرّفات المتبقّي على قواعد البيانات التي سبق
 * زرعها بالمخطط القديم (قبل تحويل المعرّفات إلى 111/222/333 للأطباء،
 * 0000+ للموظفين، 00000000+ للطلاب).
 *
 * التشخيص المؤكَّد (من تاريخ git لـUserSeeder، ثم أُعيد إنتاج الانهيار حرفيًا
 * على نسخة PostgreSQL محلية): المخطط الجديد لا يضيف معرّفات جديدة فحسب، بل
 * يعيد *توزيع* معرّفات كانت بحوزة صفوف أخرى مزروعة:
 *
 *     student@   20210123 -> 00000000   (كان بحوزة student-6@)
 *     student-2@ 20210456 -> 11111111   (كان بحوزة student-7@)
 *     staff@     2320     -> 0000       (كان بحوزة staff-4@)
 *     staff-2@   4491     -> 1111       (كان بحوزة staff-5@)
 *     staff-3@   7758     -> 2222       (كان بحوزة staff-6@)
 *     doctor-1@  000      -> 111        (كان بحوزة doctor-2@)
 *     doctor-2@  111      -> 222        (كان بحوزة doctor-3@)
 *
 * فأول صف يحاول الزرع تحديثه (student@ ⇒ 00000000) يصطدم بقيد unique لأن
 * الصف الذي يحمل القيمة لم يأتِ دوره بعد — وهذا بالضبط خطأ النشر:
 * «Key (identifier)=(00000000) already exists».
 *
 * الحل هنا: إفراغ كل معرّفات الصفوف المزروعة أولًا (قيم مؤقتة فريدة) ثم
 * إسناد القيم النهائية، فيصير الإسناد مستقلًا عن الترتيب تمامًا. UserSeeder
 * صار يفعل الشيء نفسه على كل تشغيل، فهذا الملف تنظيف تاريخي صريح وموثَّق
 * لقاعدة الإنتاج (ويجعل `migrate` وحده — دون زرع — يترك القاعدة متسقة).
 *
 * الخريطة أدناه نسخة مجمَّدة عن قصد: تعديل قائمة UserSeeder لاحقًا يجب ألا
 * يغيّر معنى migration نُفِّذ وانتهى.
 */
return new class extends Migration
{
    /** بادئة القيم المؤقتة أثناء مرحلة الإفراغ — لا تطابق أي مخطط معرّفات حقيقي. */
    private const PARK_PREFIX = 'seed-park:';

    /** @var array<string, string> بريد الحساب المزروع => معرّفه المقصود */
    private const CANONICAL = [
        'student@ttu.edu.jo' => '00000000',
        'student-2@ttu.edu.jo' => '11111111',
        'student-3@ttu.edu.jo' => '20458173',
        'student-4@ttu.edu.jo' => '30861249',
        'student-5@ttu.edu.jo' => '40273958',
        'student-6@ttu.edu.jo' => '50692134',
        'student-7@ttu.edu.jo' => '60148527',
        'student-8@ttu.edu.jo' => '70385691',
        'student-9@ttu.edu.jo' => '80217364',
        'student-10@ttu.edu.jo' => '90546823',
        'staff@ttu.edu.jo' => '0000',
        'staff-2@ttu.edu.jo' => '1111',
        'staff-3@ttu.edu.jo' => '2222',
        'staff-4@ttu.edu.jo' => '3333',
        'staff-5@ttu.edu.jo' => '4827',
        'staff-6@ttu.edu.jo' => '6193',
        'staff-7@ttu.edu.jo' => '3054',
        'staff-8@ttu.edu.jo' => '7716',
        'staff-9@ttu.edu.jo' => '2469',
        'staff-10@ttu.edu.jo' => '8302',
        'staff-11@ttu.edu.jo' => '5581',
        'staff-12@ttu.edu.jo' => '9047',
        'staff-13@ttu.edu.jo' => '1738',
        'doctor-1@ttu.edu.jo' => '111',
        'doctor-2@ttu.edu.jo' => '222',
        'doctor-3@ttu.edu.jo' => '333',
        'admin@ttu.edu.jo' => 'admin@ttu.edu.jo',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'identifier')) {
            return;
        }

        DB::transaction(function () {
            $emails = array_keys(self::CANONICAL);

            // الصفوف المزروعة الموجودة فعلًا. قاعدة جديدة تمامًا => لا شيء
            // يُنظَّف، وUserSeeder يُنشئها بعد قليل بقيمها الصحيحة.
            $rows = DB::table('users')
                ->whereIn('email', $emails)
                ->pluck('id', 'email');

            if ($rows->isEmpty()) {
                return;
            }

            // حساب غير مزروع يحتجز أحد المعرّفات المقصودة (مثلًا طبيب أنشأته
            // الإدارة يدويًا) — لا نسحب رقمًا من حساب حقيقي بصمت داخل
            // migration. نتركه كما هو، وUserSeeder سيتوقف برسالة تسمّي الصف
            // بالضبط ليحلّه إنسان.
            $foreign = DB::table('users')
                ->whereIn('identifier', array_values(self::CANONICAL))
                ->whereNotIn('email', $emails)
                ->pluck('identifier', 'id');

            if ($foreign->isNotEmpty()) {
                Log::warning('Legacy identifier cleanup skipped: seeded identifiers are held by non-seeded accounts.', [
                    'conflicts' => $foreign->all(),
                ]);

                return;
            }

            // مرحلة 1 — إفراغ: كل صف مزروع يأخذ قيمة مؤقتة فريدة مشتقّة من id،
            // فتتحرر كل المعرّفات المقصودة دفعةً واحدة.
            foreach ($rows as $id) {
                DB::table('users')->where('id', $id)->update(['identifier' => self::PARK_PREFIX.$id]);
            }

            // مرحلة 2 — الإسناد النهائي، بلا أي اعتماد على الترتيب.
            foreach (self::CANONICAL as $email => $identifier) {
                if (! isset($rows[$email])) {
                    continue;
                }

                DB::table('users')->where('id', $rows[$email])->update(['identifier' => $identifier]);
            }
        });
    }

    public function down(): void
    {
        // لا تراجع: المخطط القديم للمعرّفات هو ما كان يسبب التعارض أصلًا،
        // وإعادته تعيد إنتاج نفس انهيار النشر.
    }
};
