<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // لا يوجد تسجيل ذاتي — كل حساب لازم يُزرع جاهزًا هنا مباشرة، وإلا ما
        // في طريقة لصاحبه يدخل.
        //
        // طول الرقم لكل دور: طالب 8 خانات، موظف 4 خانات، دكتور 3 خانات —
        // يتحقق منها assertIdentifier() أدناه على كل صف قبل الزرع، وأي رقمين
        // متطابقين بكل القائمة (بصرف النظر عن الدور) يوقفان التشغيل فورًا،
        // بدل اكتشاف تصادم لاحقًا عبر قيد unique بقاعدة البيانات فقط.
        $students = [
            ['identifier' => '00000000', 'name' => 'أحمد خالد',    'email' => 'student@ttu.edu.jo'],
            ['identifier' => '11111111', 'name' => 'سارة عبدالله', 'email' => 'student-2@ttu.edu.jo'],
            ['identifier' => '20458173', 'name' => 'عمر ياسين',    'email' => 'student-3@ttu.edu.jo'],
            ['identifier' => '30861249', 'name' => 'لمى حسن',      'email' => 'student-4@ttu.edu.jo'],
            ['identifier' => '40273958', 'name' => 'يوسف مراد',    'email' => 'student-5@ttu.edu.jo'],
            ['identifier' => '50692134', 'name' => 'رنا فؤاد',     'email' => 'student-6@ttu.edu.jo'],
            ['identifier' => '60148527', 'name' => 'كريم سامي',    'email' => 'student-7@ttu.edu.jo'],
            ['identifier' => '70385691', 'name' => 'ديما نمر',     'email' => 'student-8@ttu.edu.jo'],
            ['identifier' => '80217364', 'name' => 'نادين قطيش',   'email' => 'student-9@ttu.edu.jo'],
            ['identifier' => '90546823', 'name' => 'إياد برهم',    'email' => 'student-10@ttu.edu.jo'],
        ];

        $staffMembers = [
            ['identifier' => '0000', 'name' => 'محمد علي',   'email' => 'staff@ttu.edu.jo'],
            ['identifier' => '1111', 'name' => 'هبة سالم',   'email' => 'staff-2@ttu.edu.jo'],
            ['identifier' => '2222', 'name' => 'زياد قاسم',  'email' => 'staff-3@ttu.edu.jo'],
            ['identifier' => '3333', 'name' => 'نور إبراهيم', 'email' => 'staff-4@ttu.edu.jo'],
            ['identifier' => '4827', 'name' => 'باسل عودة',  'email' => 'staff-5@ttu.edu.jo'],
            ['identifier' => '6193', 'name' => 'ريم صالح',   'email' => 'staff-6@ttu.edu.jo'],
            ['identifier' => '3054', 'name' => 'سامر خليل',  'email' => 'staff-7@ttu.edu.jo'],
            ['identifier' => '7716', 'name' => 'ياسمين طه',  'email' => 'staff-8@ttu.edu.jo'],
            ['identifier' => '2469', 'name' => 'فادي حمدان', 'email' => 'staff-9@ttu.edu.jo'],
            ['identifier' => '8302', 'name' => 'ريما شاهين', 'email' => 'staff-10@ttu.edu.jo'],
            ['identifier' => '5581', 'name' => 'وليد عاصي',  'email' => 'staff-11@ttu.edu.jo'],
            ['identifier' => '9047', 'name' => 'هناء ذيب',   'email' => 'staff-12@ttu.edu.jo'],
            ['identifier' => '1738', 'name' => 'مراد سليم',  'email' => 'staff-13@ttu.edu.jo'],
        ];

        // ثلاثة حسابات دكاترة ثابتة فقط — لا يوجد تسجيل عام لهذا الدور.
        $doctors = [
            ['identifier' => '111', 'name' => 'د. أشرف جرابعة', 'email' => 'doctor-1@ttu.edu.jo'],
            ['identifier' => '222', 'name' => 'د. سارة يوسف',   'email' => 'doctor-2@ttu.edu.jo'],
            ['identifier' => '333', 'name' => 'د. خالد ناصر',   'email' => 'doctor-3@ttu.edu.jo'],
        ];

        $this->assertNoDuplicateIdentifiers([
            ...array_column($students, 'identifier'),
            ...array_column($staffMembers, 'identifier'),
            ...array_column($doctors, 'identifier'),
        ]);

        // updateOrCreate (لا firstOrCreate) لكل الأدوار الثلاثة عمدًا — هذه
        // حسابات تجريبية ثابتة (بريدها هو مفتاح المطابقة)، لا بيانات مستخدمين
        // حقيقيين، فإعادة ضبط identifier على كل تشغيل يُصحح تلقائيًا أي قيمة
        // فاسدة بقيت من خلل تصادم المعرّفات القديم (راجع migration
        // force_identifier_to_varchar_on_users_table) بدل تركها عالقة على أي
        // بيئة سبق زرعها قبل هذا التصحيح.
        foreach ($students as $student) {
            $this->assertIdentifier($student['identifier'], 8, 'student');

            User::updateOrCreate(
                ['email' => $student['email']],
                [
                    'name' => $student['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'identifier' => $student['identifier'],
                ],
            );
        }

        foreach ($staffMembers as $staff) {
            $this->assertIdentifier($staff['identifier'], 4, 'staff');

            User::updateOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'password' => Hash::make('password'),
                    'role' => 'staff',
                    'identifier' => $staff['identifier'],
                ],
            );
        }

        // حساب المدير الثابت — لا يوجد تسجيل عام لهذا الدور، ويدخل ببريده لا
        // برقم معرّف (identifier هنا مجرد قيمة تُرضي قيد unique، غير مستخدمة
        // فعليًا بتسجيل الدخول).
        User::updateOrCreate(
            ['email' => 'admin@ttu.edu.jo'],
            [
                'name' => 'إدارة عيادة TTU',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'identifier' => 'admin@ttu.edu.jo',
            ],
        );

        foreach ($doctors as $doctor) {
            $this->assertIdentifier($doctor['identifier'], 3, 'doctor');

            User::updateOrCreate(
                ['email' => $doctor['email']],
                [
                    'name' => $doctor['name'],
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'identifier' => $doctor['identifier'],
                ],
            );
        }
    }

    /**
     * يتحقق أن معرّف الدخول رقمي بحت وبعدد الخانات المتوقع تمامًا لدوره —
     * نفس القاعدة المفروضة على إدخال أرقام الأطباء عبر لوحة الإدارة
     * (AdminController::storeDoctor/updateDoctor)، مطبَّقة هنا أيضًا على
     * بيانات الزرع الثابتة كي لا يفلت خطأ كتابة (خانة ناقصة، حرف غير رقمي)
     * إلى قاعدة البيانات بصمت.
     */
    private function assertIdentifier(string $identifier, int $expectedDigits, string $role): void
    {
        if (! preg_match("/^\d{{$expectedDigits}}$/", $identifier)) {
            throw new \InvalidArgumentException(
                "Seeded {$role} identifier \"{$identifier}\" must be exactly {$expectedDigits} numeric digits."
            );
        }
    }

    /**
     * يمنع تصادم المعرّفات بين أي حسابين مزروعين (بصرف النظر عن الدور) قبل
     * محاولة الزرع أصلًا — بدل الاعتماد فقط على قيد unique بقاعدة البيانات،
     * الذي كان سيُفشل التشغيل جزئيًا (بعض الحسابات تُزرع، البقية تفشل) بدل
     * رفض القائمة كاملة بخطأ واضح.
     */
    private function assertNoDuplicateIdentifiers(array $identifiers): void
    {
        $duplicates = array_keys(array_filter(array_count_values($identifiers), fn ($count) => $count > 1));

        if ($duplicates !== []) {
            throw new \InvalidArgumentException(
                'Duplicate seeded identifiers found: '.implode(', ', $duplicates)
            );
        }
    }
}
