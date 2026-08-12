<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إصلاح خلل تصادم المعرّفات: "000" و"0000" كانا يتحوّلان لنفس المستخدم عند
 * الدخول. السبب المؤكَّد (بالتجربة المباشرة على نسخة PostgreSQL محلية، لا
 * افتراضًا): تشغيل كل ملفات الـmigrations الحالية من الصفر ينتج عمود
 * identifier كـ varchar سليم (تحقّقنا عبر \d users)، والمقارنة عليه تعمل
 * بشكل صحيح (raw كـ raw، لا تصادم). لكن إعادة نفس العمود يدويًا كـ integer
 * أعادت إنتاج الخلل فورًا (Postgres يرفض حتى إعادة القيد الفريد بخطأ
 * "Key (identifier)=(0) is duplicated" لأن "000"‎/"0000"‎/"0" كلها تُسقَط
 * لنفس القيمة الرقمية 0). هذا يعني أن migration
 * 2026_07_24_213851_add_role_and_identifier_to_users_table.php الحالي
 * (الذي يصرّح varchar) على الأرجح عُدِّل بمكانه بعد أن كان integer وبعد أن
 * سبق تنفيذه فعليًا على قاعدة بيانات حيّة — تعديل ملف migration بعد تنفيذه
 * لا يُعاد تطبيقه تلقائيًا (نفس الفخ الذي تجنّبه هذا المشروع صراحة بملفي
 * الحذف بتاريخ 2026-08-09 عبر migration منفصل بدل تعديل ملف قديم).
 *
 * هذا الملف يفرض النوع الصحيح بصرف النظر عن حالة كل بيئة حاليًا (يعمل بأمان
 * سواء كان العمود integer فعليًا هناك، أو varchar سليمًا أصلًا هنا/بالاختبارات)
 * — USING identifier::text يحوّل أي قيمة رقمية لتمثيلها النصي المطابق، ولا
 * يغيّر شيئًا لو كان العمود نصيًا أصلًا.
 *
 * ملاحظة مهمة حول استعادة البيانات: لو كان عمود الإنتاج فعلاً integer، فإن
 * "000" و"0000" و"0" كانت قد تصادمت فعليًا على مستوى القيمة المخزَّنة نفسها
 * قبل هذا الملف — تحويل 0 (integer) إلى "0" (نص) لاحقًا لا يمكنه استرجاع أي
 * أصفار بادئة ضاعت وقت التصادم الأصلي، لأن تلك المعلومة غير موجودة أصلًا
 * بالقيمة المخزَّنة. الإصلاح العملي لكل الحسابات المزروعة (وليست بيانات
 * مستخدمين حقيقيين) هو إعادة زرعها بمعرّفات المخطط الجديد عبر
 * UserSeeder (الذي صار يستخدم updateOrCreate بدل firstOrCreate لهذا
 * السبب بالذات — راجع الملف).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN identifier TYPE varchar(255) USING identifier::text');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('identifier')->change();
            });
        }
    }

    public function down(): void
    {
        // لا يوجد شيء آمن نتراجع عنه هنا: العمود يجب أن يبقى varchar بكل
        // الأحوال (هذا هو النوع الصحيح أصلًا حسب migration الإنشاء)، وأي
        // تراجع لـ integer يعيد إنتاج نفس خلل التصادم المُصلَح بهذا الملف.
    }
};
