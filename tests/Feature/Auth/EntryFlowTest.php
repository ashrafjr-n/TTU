<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_role_badge_when_role_given(): void
    {
        $response = $this->get('/login?role=student');

        $response->assertStatus(200);
        $response->assertSee('تسجيل دخول الطالب');
    }

    public function test_login_page_falls_back_to_generic_when_no_role_given(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('تسجيل الدخول');
        $response->assertDontSee('تغيير النوع');
    }

    /**
     * الأدوار الأربعة كلها صارت مقبولة (كل بطاقة بالرئيسية تمرر دورها) —
     * المرفوض هو ما لا يطابق أيًا منها فقط.
     */
    public function test_login_page_ignores_unknown_role_query_value(): void
    {
        $response = $this->get('/login?role=hacker');

        $response->assertStatus(200);
        $response->assertDontSee('تغيير النوع');
    }

    public function test_login_page_shows_the_badge_for_doctor_and_admin_too(): void
    {
        $this->get('/login?role=doctor')
            ->assertStatus(200)
            ->assertSee('تسجيل دخول الطبيب');

        $this->get('/login?role=admin')
            ->assertStatus(200)
            ->assertSee('تسجيل دخول المدير');
    }

    public function test_failed_login_redirects_back_with_role_preserved(): void
    {
        $response = $this->from('/login?role=staff')
            ->post(route('login'), ['login' => 'nobody@test.com', 'password' => 'wrong']);

        $response->assertRedirect('/login?role=staff');
    }

    public function test_login_page_hides_the_notification_bell_for_guests(): void
    {
        $response = $this->get('/login');

        $response->assertDontSee('id="notif-toggle"', false);
    }

    public function test_guest_header_shows_the_notification_bell_for_authenticated_users(): void
    {
        $user = \App\Models\User::factory()->create(['identifier' => fake()->unique()->numerify('########')]);

        $response = $this->actingAs($user)->get('/about');

        $response->assertSee('id="notif-toggle"', false);
    }
}
