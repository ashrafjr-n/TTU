<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * انحدار لانهيار النشر على Render:
 * «duplicate key value violates unique constraint "users_identifier_unique",
 *   Key (identifier)=(00000000) already exists».
 *
 * السبب لم يكن مفتاح المطابقة (البريد صحيح أصلًا)، بل أن المخطط الجديد يعيد
 * *توزيع* معرّفات كانت بحوزة صفوف مزروعة أخرى: student@ يأخذ 00000000 الذي
 * كان بحوزة student-6@. التحديث صفًا تلو الآخر يجعل القيمة الجديدة محجوزة
 * لصف لم يأتِ دوره بعد، فيرفضها قيد unique.
 */
class UserSeederIdentifierTest extends TestCase
{
    use RefreshDatabase;

    /** المخطط القديم الذي زُرعت به قاعدة الإنتاج (الحالة قبل النشر). */
    private const LEGACY = [
        'student@ttu.edu.jo' => ['20210123', 'student'],
        'student-2@ttu.edu.jo' => ['20210456', 'student'],
        'student-3@ttu.edu.jo' => ['20210789', 'student'],
        'student-4@ttu.edu.jo' => ['20210999', 'student'],
        'student-5@ttu.edu.jo' => ['20210555', 'student'],
        'student-6@ttu.edu.jo' => ['00000000', 'student'],
        'student-7@ttu.edu.jo' => ['11111111', 'student'],
        'student-8@ttu.edu.jo' => ['22222222', 'student'],
        'staff@ttu.edu.jo' => ['2320', 'staff'],
        'staff-2@ttu.edu.jo' => ['4491', 'staff'],
        'staff-3@ttu.edu.jo' => ['7758', 'staff'],
        'staff-4@ttu.edu.jo' => ['0000', 'staff'],
        'staff-5@ttu.edu.jo' => ['1111', 'staff'],
        'staff-6@ttu.edu.jo' => ['2222', 'staff'],
        'admin@ttu.edu.jo' => ['admin@ttu.edu.jo', 'admin'],
        'doctor-1@ttu.edu.jo' => ['000', 'doctor'],
        'doctor-2@ttu.edu.jo' => ['111', 'doctor'],
        'doctor-3@ttu.edu.jo' => ['222', 'doctor'],
    ];

    private function seedLegacyState(): void
    {
        foreach (self::LEGACY as $email => [$identifier, $role]) {
            DB::table('users')->insert([
                'name' => 'legacy '.$email,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => 'x',
                'role' => $role,
                'identifier' => $identifier,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @return array<string, string> بريد => معرّف، كما هو مقصود بالمخطط الجديد */
    private function expectedMap(): array
    {
        return collect(UserSeeder::accounts())
            ->pluck('identifier', 'email')
            ->all();
    }

    private function assertDatabaseMatchesIntendedScheme(): void
    {
        $expected = $this->expectedMap();

        $actual = User::whereIn('email', array_keys($expected))
            ->pluck('identifier', 'email')
            ->all();

        ksort($expected);
        ksort($actual);

        $this->assertSame($expected, $actual);

        // لا معرّفات مؤقتة متسرّبة، ولا تكرار
        $this->assertSame(0, User::where('identifier', 'like', 'seed-park:%')->count());
        $this->assertSame(User::count(), User::distinct()->count('identifier'));
    }

    public function test_it_seeds_a_fresh_database(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertSame(27, User::count());
        $this->assertDatabaseMatchesIntendedScheme();

        // المخطط المقصود: 111/222/333 للأطباء، 0000+ للموظفين، 00000000+ للطلاب
        $this->assertSame(['111', '222', '333'], User::where('role', 'doctor')->orderBy('identifier')->pluck('identifier')->all());
        $this->assertSame('0000', User::where('email', 'staff@ttu.edu.jo')->value('identifier'));
        $this->assertSame('00000000', User::where('email', 'student@ttu.edu.jo')->value('identifier'));
    }

    /**
     * السيناريو الحرفي الذي أسقط النشر: صفوف قديمة تحتجز المعرّفات الجديدة.
     */
    public function test_it_recovers_when_legacy_rows_hold_the_new_identifiers(): void
    {
        $this->seedLegacyState();

        // شرط التصادم قائم فعلًا قبل الزرع: 00000000 بحوزة student-6@ لا student@
        $this->assertSame('student-6@ttu.edu.jo', User::where('identifier', '00000000')->value('email'));

        $this->seed(UserSeeder::class);

        $this->assertDatabaseMatchesIntendedScheme();
        $this->assertSame('student@ttu.edu.jo', User::where('identifier', '00000000')->value('email'));
    }

    /**
     * أسوأ حالة ممكنة من هذا الصنف: كل صف يحتجز معرّف الصف التالي (دوران كامل).
     */
    public function test_it_recovers_from_a_full_rotation_of_every_identifier(): void
    {
        $this->seed(UserSeeder::class);

        $rows = User::orderBy('id')->get(['id', 'identifier']);
        $identifiers = $rows->pluck('identifier')->all();
        $rotated = [...array_slice($identifiers, 1), $identifiers[0]];

        // أفرغ أولًا (وإلا اصطدم التدوير نفسه بقيد unique)، ثم دوّر
        $rows->each(fn (User $user) => User::whereKey($user->id)->update(['identifier' => 'rot:'.$user->id]));
        $rows->each(fn (User $user, int $i) => User::whereKey($user->id)->update(['identifier' => $rotated[$i]]));

        $this->assertNotSame('00000000', User::where('email', 'student@ttu.edu.jo')->value('identifier'));

        $this->seed(UserSeeder::class);

        $this->assertDatabaseMatchesIntendedScheme();
    }

    /**
     * docker-entrypoint.sh ينفّذ migrate + db:seed عند كل إقلاع للحاوية، لا
     * عند أول نشر فقط — فالتكرار لازم يبقى بلا أثر جانبي إلى الأبد.
     */
    public function test_running_it_repeatedly_is_idempotent(): void
    {
        $this->seedLegacyState();

        for ($boot = 1; $boot <= 3; $boot++) {
            $this->seed(UserSeeder::class);

            $this->assertSame(27, User::count(), "after boot {$boot}");
            $this->assertDatabaseMatchesIntendedScheme();
        }
    }

    /**
     * حساب غير مزروع يحتجز معرّفًا مزروعًا (مثلًا طبيب أنشأته الإدارة) — يجب
     * التوقف برسالة تسمّي الصف بالضبط، لا سحب الرقم منه بصمت ولا خطأ قيد خام.
     */
    public function test_it_fails_loudly_when_a_non_seeded_account_holds_a_seeded_identifier(): void
    {
        $this->seed(UserSeeder::class);

        // حرّر 333 من صاحبه المزروع ثم أعطه لحساب خارج قائمة الزرع
        User::where('email', 'doctor-3@ttu.edu.jo')->update(['identifier' => '999']);
        $intruder = User::factory()->create([
            'email' => 'doctor-333@ttu.edu.jo',
            'role' => 'doctor',
            'identifier' => '333',
        ]);

        try {
            $this->seed(UserSeeder::class);
            $this->fail('Expected the seeder to refuse to overwrite a non-seeded account.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('non-seeded account', $e->getMessage());
            $this->assertStringContainsString('#'.$intruder->id, $e->getMessage());
            $this->assertStringContainsString('doctor-333@ttu.edu.jo', $e->getMessage());
            $this->assertStringContainsString('"333"', $e->getMessage());
        }

        // ولا كتابة جزئية: الحالة كما كانت تمامًا قبل المحاولة
        $this->assertSame('999', User::where('email', 'doctor-3@ttu.edu.jo')->value('identifier'));
        $this->assertSame('333', $intruder->fresh()->identifier);
        $this->assertSame(0, User::where('identifier', 'like', 'seed-park:%')->count());

        // وبعد حلّ التعارض يمر الزرع طبيعيًا
        $intruder->delete();
        $this->seed(UserSeeder::class);
        $this->assertDatabaseMatchesIntendedScheme();
    }

    /**
     * migration التنظيف لمرة واحدة: يصلح البيانات المنحرفة وحده، دون زرع، كي
     * تبقى القاعدة متسقة حتى لو نُفِّذ migrate بلا db:seed.
     */
    public function test_the_cleanup_migration_resolves_legacy_conflicts_on_its_own(): void
    {
        $this->seedLegacyState();

        $migration = require database_path('migrations/2026_08_13_120000_resolve_legacy_identifier_conflicts.php');
        $migration->up();

        // كل صف موجود صار على معرّفه المقصود — بلا أي زرع
        $expected = $this->expectedMap();

        foreach (array_keys(self::LEGACY) as $email) {
            $this->assertSame($expected[$email], User::where('email', $email)->value('identifier'), $email);
        }

        $this->assertSame(0, User::where('identifier', 'like', 'seed-park:%')->count());

        // وتشغيله على قاعدة فارغة لا يفعل شيئًا ولا يفشل
        User::query()->delete();
        $migration->up();
        $this->assertSame(0, User::count());
    }
}
