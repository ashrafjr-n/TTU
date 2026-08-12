<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * بادئة المعرّفات المؤقتة المستخدمة أثناء "مرحلة الإيقاف المؤقت" (park)
     * داخل معاملة الزرع. لا تطابق أي مخطط حقيقي (المعرّفات الحقيقية أرقام
     * بحتة) ولا تبقى بقاعدة البيانات بعد انتهاء المعاملة.
     */
    private const PARK_PREFIX = 'seed-park:';

    /**
     * طول المعرّف المطلوب لكل دور — يتحقق منه assertIdentifier() على كل صف.
     */
    private const DIGITS_PER_ROLE = ['student' => 8, 'staff' => 4, 'doctor' => 3];

    public function run(): void
    {
        $accounts = self::accounts();

        $this->assertSeedListIsConsistent($accounts);

        // معاملة واحدة تلفّ الزرع كله: قبلها كان أي فشل بمنتصف القائمة يترك
        // جزءًا من الحسابات مزروعًا بالمخطط الجديد وجزءًا بالقديم (حالة
        // نصفية لا يمكن لأحد الدخول منها بثقة). الآن إمّا تنجح القائمة
        // كاملة أو لا يتغيّر شيء.
        DB::transaction(function () use ($accounts) {
            $this->assertNoForeignIdentifierHolders($accounts);
            $this->parkExistingSeededIdentifiers($accounts);

            foreach ($accounts as $account) {
                // المطابقة بالبريد: هو المُعرِّف المستقر لـ"هذا هو نفس الحساب
                // المزروع" — بعكس identifier الذي تغيّر مخططه أكثر من مرة.
                User::updateOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $account['name'],
                        'password' => Hash::make('password'),
                        'role' => $account['role'],
                        'identifier' => $account['identifier'],
                    ],
                );
            }
        });
    }

    /**
     * الحسابات المزروعة الثابتة — لا يوجد تسجيل ذاتي، فكل حساب لازم يُزرع
     * جاهزًا هنا وإلا ما في طريقة لصاحبه يدخل.
     *
     * طول الرقم لكل دور: طالب 8 خانات، موظف 4، دكتور 3.
     *
     * @return list<array{email:string, name:string, role:string, identifier:string}>
     */
    public static function accounts(): array
    {
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

        $accounts = [];

        foreach ($students as $student) {
            $accounts[] = [...$student, 'role' => 'student'];
        }

        foreach ($staffMembers as $staff) {
            $accounts[] = [...$staff, 'role' => 'staff'];
        }

        foreach ($doctors as $doctor) {
            $accounts[] = [...$doctor, 'role' => 'doctor'];
        }

        // حساب المدير الثابت — يدخل ببريده لا برقم معرّف (identifier هنا مجرد
        // قيمة تُرضي قيد unique، غير مستخدمة فعليًا بتسجيل الدخول)، فهو
        // مستثنى من قاعدة "أرقام فقط" المطبَّقة على بقية الأدوار.
        $accounts[] = [
            'identifier' => 'admin@ttu.edu.jo',
            'name' => 'إدارة عيادة TTU',
            'email' => 'admin@ttu.edu.jo',
            'role' => 'admin',
        ];

        return $accounts;
    }

    /**
     * ينقل معرّفات كل الصفوف المزروعة الموجودة مسبقًا إلى قيم مؤقتة فريدة قبل
     * كتابة القيم النهائية.
     *
     * هذه هي النقطة الجوهرية بكل هذا الملف: مفتاح المطابقة (البريد) كان سليمًا
     * أصلًا، والانهيار الذي كان يحصل على Render سببه أن المخطط الجديد يعيد
     * *توزيع* نفس مجموعة المعرّفات على صفوف أخرى — student@ يأخذ 00000000
     * الذي كان بحوزة student-6@، وdoctor-1@ يأخذ 111 الذي كان بحوزة
     * doctor-2@، وهكذا. تحديث صف تلو الآخر يعني أن القيمة الجديدة لا تزال
     * محجوزة لصف لم يأتِ دوره بعد، فيرفضها قيد unique فورًا. إفراغ كل
     * المعرّفات أولًا يجعل الإسناد اللاحق غير معتمد على الترتيب إطلاقًا،
     * فيغطي أي تبديل أو دوران مهما كان شكله، اليوم أو بأي مخطط قادم.
     *
     * القيمة المؤقتة مشتقّة من معرّف الصف نفسه (id) فهي فريدة بالضرورة، ولا
     * تبقى بعد انتهاء المعاملة.
     *
     * @param  list<array{email:string, name:string, role:string, identifier:string}>  $accounts
     */
    private function parkExistingSeededIdentifiers(array $accounts): void
    {
        $existing = User::whereIn('email', array_column($accounts, 'email'))
            ->pluck('id');

        foreach ($existing as $id) {
            User::whereKey($id)->update(['identifier' => self::PARK_PREFIX.$id]);
        }
    }

    /**
     * يرفض التشغيل لو كان أحد المعرّفات المزروعة بحوزة حساب *غير* مزروع
     * (بريده ليس من قائمة الزرع) — مثل طبيب أنشأته الإدارة يدويًا بنفس الرقم.
     *
     * الخيار المتعمَّد هنا هو التوقف بخطأ واضح لا "تصحيح" صامت: سحب الرقم من
     * حساب حقيقي يعني قطع تسجيل دخوله دون أن يدري أحد. الرسالة تسمّي الصف
     * المتعارض بالضبط (id/بريد/رقم) ليحلّه إنسان، بدل خطأ قيد خام من
     * Postgres بمنتصف النشر لا يقول أي صف السبب.
     *
     * @param  list<array{email:string, name:string, role:string, identifier:string}>  $accounts
     */
    private function assertNoForeignIdentifierHolders(array $accounts): void
    {
        $conflicts = User::whereIn('identifier', array_column($accounts, 'identifier'))
            ->whereNotIn('email', array_column($accounts, 'email'))
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'identifier']);

        if ($conflicts->isEmpty()) {
            return;
        }

        $details = $conflicts
            ->map(fn (User $user) => sprintf(
                '  - user #%d <%s> (%s) holds identifier "%s"',
                $user->id,
                $user->email,
                $user->name,
                $user->identifier,
            ))
            ->implode(PHP_EOL);

        throw new RuntimeException(
            'UserSeeder cannot assign its identifiers: the following non-seeded account(s) already hold them.'.PHP_EOL
            .$details.PHP_EOL
            .'Nothing was written (the whole seed runs in one transaction). Give those accounts a different '
            .'identifier — or delete them if they are stale — and run the seeder again.'
        );
    }

    /**
     * فحوص على قائمة الزرع نفسها قبل لمس قاعدة البيانات: طول/نوع كل معرّف
     * حسب دوره، ولا تكرار بالمعرّفات ولا بالبُرد. اكتشاف خطأ كتابة هنا أوضح
     * بكثير من اكتشافه لاحقًا كخطأ قيد فريد بقاعدة البيانات.
     *
     * @param  list<array{email:string, name:string, role:string, identifier:string}>  $accounts
     */
    private function assertSeedListIsConsistent(array $accounts): void
    {
        foreach ($accounts as $account) {
            $digits = self::DIGITS_PER_ROLE[$account['role']] ?? null;

            if ($digits !== null && ! preg_match("/^\d{{$digits}}$/", $account['identifier'])) {
                throw new RuntimeException(sprintf(
                    'Seeded %s identifier "%s" must be exactly %d numeric digits.',
                    $account['role'],
                    $account['identifier'],
                    $digits,
                ));
            }
        }

        $this->assertNoDuplicates(array_column($accounts, 'identifier'), 'identifiers');
        $this->assertNoDuplicates(array_column($accounts, 'email'), 'emails');
    }

    /**
     * @param  list<string>  $values
     */
    private function assertNoDuplicates(array $values, string $label): void
    {
        $duplicates = array_keys(array_filter(array_count_values($values), fn ($count) => $count > 1));

        if ($duplicates !== []) {
            throw new RuntimeException(
                "Duplicate seeded {$label} found: ".implode(', ', $duplicates)
            );
        }
    }
}
