<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * جزآن مترابطان: بطاقات الأدوار بالرئيسية تمرر نوع الحساب لصفحة الدخول
 * (فيتغير اسم حقل الدخول)، وتُقفل بطاقات الأدوار الأخرى لمن سجّل دخوله.
 * القفل عرضي فقط — الحماية الحقيقية تبقى على middleware الأدوار، وهذا
 * ما تؤكده اختبارات الوصول المباشر بالرابط أدناه.
 */
class RoleAwareLoginAndHomeCardsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'identifier' => fake()->unique()->numerify('########'),
        ]);
    }

    // ------------------------------------------------------------------
    // 1) اسم حقل الدخول يتبع البطاقة التي جاء منها المستخدم
    // ------------------------------------------------------------------

    public static function roleFieldProvider(): array
    {
        return [
            'student' => ['student', 'الرقم الجامعي'],
            'staff' => ['staff', 'الرقم الوظيفي'],
            'doctor' => ['doctor', 'الرقم الوظيفي للطبيب'],
            'admin' => ['admin', 'البريد الإلكتروني'],
        ];
    }

    #[DataProvider('roleFieldProvider')]
    public function test_login_field_label_matches_the_role_it_was_opened_with(string $role, string $expectedLabel): void
    {
        $response = $this->get(route('login', ['role' => $role]));

        $response->assertOk();
        $response->assertSee($expectedLabel);
        $response->assertSee(__('auth_forms.login.login_field_'.$role));

        // لا تظهر الصياغة العامة المدمجة عند وجود دور
        $response->assertDontSee(__('auth_forms.login.login_field'));
    }

    public function test_login_field_labels_are_translated_in_english(): void
    {
        $expected = [
            'student' => 'University ID number',
            'staff' => 'Staff ID number',
            'doctor' => "Doctor's staff ID number",
            'admin' => 'Email address',
        ];

        foreach ($expected as $role => $label) {
            $response = $this->withSession(['locale' => 'en'])->get(route('login', ['role' => $role]));

            $response->assertOk();
            // assertSee يهرّب النص المتوقع، فتُطابَق الفاصلة العليا في
            // "Doctor's" كما تظهر فعليًا بالصفحة (&#039;).
            $response->assertSee($label);
        }
    }

    public function test_admin_entry_point_hints_an_email_field(): void
    {
        $this->get(route('login', ['role' => 'admin']))->assertSee('autocomplete="email"', false);
        $this->get(route('login', ['role' => 'student']))->assertSee('autocomplete="username"', false);
    }

    /** كل بطاقة تفتح الدخول بنوع حسابها — لا رابط دخول عام بقسم الأدوار */
    public function test_home_role_cards_link_to_login_with_their_role(): void
    {
        $response = $this->get(route('home'));

        foreach (['student', 'staff', 'doctor', 'admin'] as $role) {
            $response->assertSee(route('login', ['role' => $role]), false);
        }
    }

    // ------------------------------------------------------------------
    // مسار الدخول المباشر (Bookmark) — الصياغة العامة كما كانت
    // ------------------------------------------------------------------

    public function test_direct_login_without_a_role_keeps_the_generic_label(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(__('auth_forms.login.login_field'));
        $response->assertSee('الرقم الجامعي أو الوظيفي');
    }

    public function test_unknown_role_falls_back_to_the_generic_label(): void
    {
        $response = $this->get(route('login', ['role' => 'hacker']));

        $response->assertOk();
        $response->assertSee(__('auth_forms.login.login_field'));
        $response->assertDontSee(__('auth_forms.account_type'));
    }

    // ------------------------------------------------------------------
    // 2) قفل بطاقات الأدوار لمن سجّل دخوله
    // ------------------------------------------------------------------

    public function test_guest_sees_all_four_cards_unlocked(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('role-locked', false);
        $response->assertDontSee('aria-disabled="true"', false);
    }

    public static function roleProvider(): array
    {
        return [
            'student' => ['student'],
            'staff' => ['staff'],
            'doctor' => ['doctor'],
            'admin' => ['admin'],
        ];
    }

    /**
     * لكل دور: بطاقته وحدها تبقى فعّالة (رابط دخولها موجود) والثلاث الأخرى
     * مقفلة — ثلاث بطاقات تحمل role-locked بالضبط.
     */
    #[DataProvider('roleProvider')]
    public function test_authenticated_user_sees_only_their_own_card_active(string $role): void
    {
        $response = $this->actingAs($this->user($role))->get(route('home'));

        $response->assertOk();
        $this->assertSame(
            3,
            substr_count($response->getContent(), 'role-locked'),
            "المتوقع قفل ٣ بطاقات فقط لدور $role",
        );

        // بطاقة الدور نفسه تبقى قابلة للنقر
        $response->assertSee(route('login', ['role' => $role]), false);

        // ولا رابط لبطاقات الأدوار الأخرى
        foreach (['student', 'staff', 'doctor', 'admin'] as $other) {
            if ($other !== $role) {
                $response->assertDontSee(route('login', ['role' => $other]), false);
            }
        }
    }

    #[DataProvider('roleProvider')]
    public function test_locked_cards_are_marked_disabled_for_assistive_tech(string $role): void
    {
        $response = $this->actingAs($this->user($role))->get(route('home'));

        $this->assertSame(3, substr_count($response->getContent(), 'aria-disabled="true"'));
        $this->assertSame(3, substr_count($response->getContent(), 'tabindex="-1"'));
    }

    // ------------------------------------------------------------------
    // القفل عرضي — الحماية الحقيقية على الـmiddleware
    // ------------------------------------------------------------------

    /**
     * الوصول المباشر بالرابط للوحة دور آخر يبقى ممنوعًا من الخادم (403)
     * بغض النظر عن واجهة الرئيسية — أي أن القفل البصري ليس الحاجز الوحيد.
     */
    public function test_cross_role_dashboard_urls_are_blocked_server_side(): void
    {
        $dashboards = [
            'student' => '/dashboard/student',
            'staff' => '/dashboard/staff',
            'doctor' => '/dashboard/doctor',
            'admin' => '/admin',
        ];

        foreach ($dashboards as $ownRole => $ignored) {
            $user = $this->user($ownRole);

            foreach ($dashboards as $targetRole => $url) {
                $response = $this->actingAs($user)->get($url);

                if ($targetRole === $ownRole) {
                    $response->assertOk();
                } else {
                    $response->assertForbidden();
                }
            }
        }
    }

    /** الزائر غير المسجّل يُحوَّل لصفحة الدخول لا إلى لوحة أي دور */
    public function test_guest_is_redirected_from_every_dashboard(): void
    {
        foreach (['/dashboard/student', '/dashboard/staff', '/dashboard/doctor', '/admin'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }
}
