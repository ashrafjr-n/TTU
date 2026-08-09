<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحات المصادقة (تسجيل الدخول/تأكيد كلمة المرور/تأكيد البريد) كانت سابقًا
 * بلا هيدر ولا زر لغة إطلاقًا (Breeze الافتراضي بدون تخصيص) — هذا الاختبار
 * يتأكد أنها الآن جزء فعلي من نظام تبديل اللغة، وليست استثناءً منسيًا.
 */
class AuthPagesLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_switches_language_and_direction(): void
    {
        $ar = $this->get(route('login'));
        $ar->assertOk();
        $ar->assertSee('dir="rtl"', false);
        $ar->assertSee('تسجيل الدخول');

        $en = $this->withSession(['locale' => 'en'])->get(route('login'));
        $en->assertOk();
        $en->assertSee('dir="ltr"', false);
        $en->assertSee('Log In');
        $en->assertDontSee('تسجيل الدخول');
    }

    public function test_confirm_password_page_translates(): void
    {
        $user = User::factory()->create(['identifier' => fake()->unique()->numerify('########')]);

        $en = $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('password.confirm'));
        $en->assertOk();
        $en->assertSee('Confirm your password');
    }

    public function test_verify_email_page_translates(): void
    {
        $user = User::factory()->unverified()->create(['identifier' => fake()->unique()->numerify('########')]);

        $en = $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('verification.notice'));
        $en->assertOk();
        $en->assertSee('Verify your email');
    }
}
