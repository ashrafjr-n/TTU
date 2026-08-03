<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_role_badge_and_register_link_when_role_given(): void
    {
        $response = $this->get('/login?role=student');

        $response->assertStatus(200);
        $response->assertSee('تسجيل دخول الطالب');
        $response->assertSee(route('register', ['role' => 'student']), false);
    }

    public function test_login_page_falls_back_to_generic_when_no_role_given(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('تسجيل الدخول');
        $response->assertSee(route('home').'#roles', false);
    }

    public function test_login_page_ignores_invalid_role_query_value(): void
    {
        $response = $this->get('/login?role=doctor');

        $response->assertStatus(200);
        $response->assertSee(route('home').'#roles', false);
    }

    public function test_failed_login_redirects_back_with_role_preserved(): void
    {
        $response = $this->from('/login?role=staff')
            ->post(route('login'), ['login' => 'nobody@test.com', 'password' => 'wrong']);

        $response->assertRedirect('/login?role=staff');
    }

    public function test_register_page_renders_when_a_registrable_role_is_given(): void
    {
        $response = $this->get('/register?role=student');

        $response->assertStatus(200);
        $response->assertSee('إنشاء حساب طالب');
    }

    public function test_register_page_redirects_to_role_picker_without_a_valid_role(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('home').'#roles');
    }

    public function test_register_page_redirects_for_a_non_registrable_role(): void
    {
        // الدكتور مستثنى من التسجيل الذاتي — حساباته ثابتة عبر Seeder فقط
        $response = $this->get('/register?role=doctor');

        $response->assertRedirect(route('home').'#roles');
    }
}
