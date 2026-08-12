<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * حارس نظام المعرّفات بالكامل. سبب وجوده: خلل ظهر بالإنتاج مرتين — أرقام
 * الأطباء 111/222/333 كانت تعطي طبيبًا خطأً لأن UserSeeder كان يتوقف كليًا
 * (طبيب أنشأته الإدارة كان يحتجز الرقم 333)، فتبقى الحسابات الـ27 على
 * معرّفاتها القديمة. الاختبارات هنا تغطي السلسلة كلها: قائمة الزرع، الصفوف
 * الناتجة، ثم تسجيل دخول فعلي بكل معرّف.
 */
class SeededIdentifierLoginTest extends TestCase
{
    use RefreshDatabase;

    private function seedUsers(): void
    {
        $this->seed(UserSeeder::class);
    }

    // ------------------------------------------------------------------
    // الخلل المُبلَّغ عنه تحديدًا: أرقام الأطباء الثلاثة
    // ------------------------------------------------------------------

    /**
     * الاختبار الذي كان سيلتقط الخلل: 111/222/333 يجب أن تعطي ثلاثة أطباء
     * مختلفين بالأسماء المتوقعة — لا نفس الطبيب ثلاث مرات.
     */
    public function test_each_doctor_identifier_resolves_to_a_distinct_doctor(): void
    {
        $this->seedUsers();

        // أزواج صريحة لا مفاتيح: مفتاح المصفوفة الرقمي بـPHP يتحوّل تلقائيًا
        // لعدد صحيح، فيصل للطلب كعدد لا كنص وتسقط قاعدة التحقق "string".
        $expected = [
            ['111', 'د. أشرف جرابعة'],
            ['222', 'د. سارة يوسف'],
            ['333', 'د. خالد ناصر'],
        ];

        $resolvedIds = [];

        foreach ($expected as [$identifier, $name]) {
            $user = User::where('identifier', $identifier)->first();

            $this->assertNotNull($user, "لا يوجد مستخدم بالمعرّف $identifier");
            $this->assertSame('doctor', $user->role);
            $this->assertSame($name, $user->name, "المعرّف $identifier أعطى الطبيب الخطأ");

            $resolvedIds[] = $user->id;
        }

        $this->assertCount(3, array_unique($resolvedIds), 'المعرّفات الثلاثة تشير لأكثر من صف واحد مشترك');
    }

    /** نفس الشيء عبر تسجيل دخول حقيقي عبر الـHTTP، لا استعلامًا فقط */
    public function test_logging_in_with_each_doctor_identifier_authenticates_a_distinct_doctor(): void
    {
        $this->seedUsers();

        // أزواج صريحة لا مفاتيح: مفتاح المصفوفة الرقمي بـPHP يتحوّل تلقائيًا
        // لعدد صحيح، فيصل للطلب كعدد لا كنص وتسقط قاعدة التحقق "string".
        $expected = [
            ['111', 'د. أشرف جرابعة'],
            ['222', 'د. سارة يوسف'],
            ['333', 'د. خالد ناصر'],
        ];

        $authenticatedIds = [];

        foreach ($expected as [$identifier, $name]) {
            $this->post(route('login'), ['login' => $identifier, 'password' => 'password'])
                ->assertRedirect(route('dashboard'));

            $this->assertAuthenticated();
            $this->assertSame($name, auth()->user()->name, "الدخول بالرقم $identifier أعطى طبيبًا خطأ");
            $this->assertSame('doctor', auth()->user()->role);

            $authenticatedIds[] = auth()->id();
            $this->post(route('logout'));
        }

        $this->assertCount(3, array_unique($authenticatedIds));
    }

    // ------------------------------------------------------------------
    // كل الحسابات الـ27
    // ------------------------------------------------------------------

    public function test_seeder_creates_all_27_accounts_with_distinct_identifiers_and_emails(): void
    {
        $this->seedUsers();

        $accounts = UserSeeder::accounts();

        $this->assertCount(27, $accounts);
        $this->assertSame(27, User::count());
        $this->assertSame(27, User::distinct()->count('identifier'));
        $this->assertSame(27, User::distinct()->count('email'));

        foreach ($accounts as $account) {
            $user = User::where('email', $account['email'])->first();

            $this->assertNotNull($user, "الحساب {$account['email']} غير مزروع");
            $this->assertSame($account['identifier'], $user->identifier, "معرّف {$account['email']} غير مطابق");
            $this->assertSame($account['name'], $user->name);
            $this->assertSame($account['role'], $user->role);
        }
    }

    /**
     * تسجيل دخول فعلي بكل معرّف مزروع (والمدير ببريده) — والتأكد أن كل واحد
     * يصادِق الحساب الصحيح تحديدًا، لا مجرد "حساب ما".
     */
    public function test_every_seeded_credential_authenticates_as_its_own_account(): void
    {
        $this->seedUsers();

        $seenIds = [];

        foreach (UserSeeder::accounts() as $account) {
            // المدير يدخل ببريده؛ بقية الأدوار برقمهم
            $credential = $account['role'] === 'admin' ? $account['email'] : $account['identifier'];

            $this->post(route('login'), ['login' => $credential, 'password' => 'password'])
                ->assertRedirect(route('dashboard'));

            $this->assertAuthenticated();

            $user = auth()->user();
            $this->assertSame($account['email'], $user->email, "الدخول بـ$credential أعطى حسابًا خطأ");
            $this->assertSame($account['name'], $user->name);
            $this->assertSame($account['role'], $user->role);

            $seenIds[] = $user->id;
            $this->post(route('logout'));
        }

        $this->assertCount(27, array_unique($seenIds), 'بعض المعرّفات تشير لنفس الحساب');
    }

    /** المطابقة نصية صرفة: "111" لا يساوي "0111" ولا "11" ولا 111 كعدد */
    public function test_identifier_lookup_is_an_exact_string_match(): void
    {
        $this->seedUsers();

        foreach (['0111', '11', '1110', '00000', '3330'] as $near) {
            $this->post(route('login'), ['login' => $near, 'password' => 'password'])
                ->assertSessionHasErrors('login');

            $this->assertGuest();
        }
    }

    /** أصفار بادئة مختلفة الطول تبقى حسابات مختلفة (خلل التصادم القديم) */
    public function test_zero_prefixed_identifiers_of_different_lengths_stay_distinct(): void
    {
        $this->seedUsers();

        $staff = User::where('identifier', '0000')->first();
        $student = User::where('identifier', '00000000')->first();

        $this->assertNotNull($staff);
        $this->assertNotNull($student);
        $this->assertNotSame($staff->id, $student->id);
        $this->assertSame('staff', $staff->role);
        $this->assertSame('student', $student->role);
    }

    // ------------------------------------------------------------------
    // السبب الجذري: طبيب من لوحة الإدارة يحتجز رقمًا محجوزًا للزرع
    // ------------------------------------------------------------------

    /**
     * هذا ما فتح الباب للخلل: الإدارة كانت تستطيع إنشاء طبيب بالرقم 333،
     * فيتوقف UserSeeder كليًا بعدها ولا تُسنَد أي معرّفات.
     */
    public function test_admin_cannot_create_a_doctor_on_a_seeder_reserved_identifier(): void
    {
        $this->seedUsers();

        $admin = User::where('role', 'admin')->first();

        foreach (['111', '222', '333'] as $reserved) {
            $response = $this->actingAs($admin)->post(route('admin.doctors.store'), [
                'name' => 'د. طبيب جديد',
                'identifier' => $reserved,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $response->assertSessionHasErrors('identifier');
        }

        $this->assertSame(3, User::where('role', 'doctor')->count(), 'لم يُنشأ أي طبيب إضافي');
    }

    public function test_admin_can_still_create_a_doctor_on_a_free_identifier(): void
    {
        $this->seedUsers();

        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'د. رانيا حداد',
            'identifier' => '405',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['identifier' => '405', 'name' => 'د. رانيا حداد', 'role' => 'doctor']);
    }

    /** طبيب مزروع يُعدَّل اسمه يحتفظ برقمه — الحارس لا يمنعه من نفسه */
    public function test_editing_a_seeded_doctor_keeps_its_own_reserved_identifier(): void
    {
        $this->seedUsers();

        $admin = User::where('role', 'admin')->first();
        $doctor = User::where('identifier', '222')->first();

        $this->actingAs($admin)->put(route('admin.doctors.update', $doctor), [
            'name' => 'د. سارة يوسف المحدّث',
            'identifier' => '222',
        ])->assertSessionHasNoErrors();

        $this->assertSame('222', $doctor->fresh()->identifier);
    }

    /** لكنه لا يستطيع أخذ رقم طبيب مزروع آخر */
    public function test_editing_a_doctor_cannot_steal_another_reserved_identifier(): void
    {
        $this->seedUsers();

        $admin = User::where('role', 'admin')->first();
        $doctor = User::where('identifier', '222')->first();

        $this->actingAs($admin)->put(route('admin.doctors.update', $doctor), [
            'name' => 'د. سارة يوسف',
            'identifier' => '111',
        ])->assertSessionHasErrors('identifier');

        $this->assertSame('222', $doctor->fresh()->identifier);
    }

    // ------------------------------------------------------------------
    // إعادة الزرع لا تكسر شيئًا (park-then-assign)
    // ------------------------------------------------------------------

    /**
     * إعادة تشغيل الزرع فوق قاعدة تحمل المخطط القديم للمعرّفات — وهو ما يحصل
     * فعليًا عند النشر — يجب أن تنتهي بالمخطط الصحيح دون خطأ قيد فريد، لأن
     * المخطط الجديد يعيد توزيع نفس الأرقام على صفوف أخرى.
     */
    public function test_reseeding_over_the_legacy_identifier_scheme_repairs_every_row(): void
    {
        $this->seedUsers();

        // إرجاع الأطباء للمخطط القديم: doctor-1=000, doctor-2=111, doctor-3=222
        User::where('email', 'doctor-1@ttu.edu.jo')->update(['identifier' => 'tmp-1']);
        User::where('email', 'doctor-2@ttu.edu.jo')->update(['identifier' => 'tmp-2']);
        User::where('email', 'doctor-3@ttu.edu.jo')->update(['identifier' => 'tmp-3']);
        User::where('email', 'doctor-1@ttu.edu.jo')->update(['identifier' => '000']);
        User::where('email', 'doctor-2@ttu.edu.jo')->update(['identifier' => '111']);
        User::where('email', 'doctor-3@ttu.edu.jo')->update(['identifier' => '222']);

        $this->seedUsers();

        $this->assertSame('111', User::where('email', 'doctor-1@ttu.edu.jo')->value('identifier'));
        $this->assertSame('222', User::where('email', 'doctor-2@ttu.edu.jo')->value('identifier'));
        $this->assertSame('333', User::where('email', 'doctor-3@ttu.edu.jo')->value('identifier'));
        $this->assertSame(27, User::count());
    }

    /** الزرع يبقى idempotent: تشغيله مرتين لا يضاعف الصفوف ولا يغيّر المعرّفات */
    public function test_seeding_twice_is_idempotent(): void
    {
        $this->seedUsers();
        $before = User::orderBy('email')->pluck('identifier', 'email')->all();

        $this->seedUsers();
        $after = User::orderBy('email')->pluck('identifier', 'email')->all();

        $this->assertSame($before, $after);
        $this->assertSame(27, User::count());
    }

    /**
     * الزرع يتوقف صراحةً — ولا يسحب الرقم بصمت — لو كان بحوزة حساب غير
     * مزروع. هذا السلوك مقصود، والحارس بلوحة الإدارة هو ما يمنع الوصول له.
     */
    public function test_seeder_refuses_to_steal_an_identifier_from_a_non_seeded_account(): void
    {
        User::create([
            'name' => 'د. طبيب أنشأته الإدارة',
            'email' => 'doctor-333@ttu.edu.jo',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'identifier' => '333',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->seedUsers();
    }
}
