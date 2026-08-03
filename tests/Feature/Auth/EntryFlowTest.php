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
}
