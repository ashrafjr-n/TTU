<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAuditTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'login-test@ttu.edu.jo',
            'password' => Hash::make('password'),
            'role' => 'student',
            'identifier' => '30000001',
        ], $overrides));
    }

    public function test_login_works_with_email_regardless_of_case(): void
    {
        $this->makeUser();

        $response = $this->post(route('login'), [
            'login' => 'LOGIN-TEST@TTU.EDU.JO',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_works_with_identifier(): void
    {
        $user = $this->makeUser();

        $this->post(route('login'), ['login' => '30000001', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_trims_surrounding_whitespace(): void
    {
        $user = $this->makeUser();

        $this->post(route('login'), ['login' => '  login-test@ttu.edu.jo  ', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_unknown_user_share_one_generic_message(): void
    {
        $this->makeUser();

        $wrongPassword = $this->post(route('login'), ['login' => 'login-test@ttu.edu.jo', 'password' => 'nope']);
        $this->assertGuest();
        $wrongPassword->assertInvalid(['login' => 'بيانات الدخول غير صحيحة.']);

        $unknownUser = $this->post(route('login'), ['login' => 'nobody@ttu.edu.jo', 'password' => 'nope']);
        $this->assertGuest();
        $unknownUser->assertInvalid(['login' => 'بيانات الدخول غير صحيحة.']);
    }

    public function test_inactive_account_gets_a_distinct_message(): void
    {
        $this->makeUser(['is_active' => false]);

        $response = $this->post(route('login'), ['login' => 'login-test@ttu.edu.jo', 'password' => 'password']);

        $response->assertInvalid(['login' => 'تم تعطيل هذا الحساب. الرجاء التواصل مع إدارة العيادة.']);
        $this->assertGuest();
    }

    public function test_rate_limit_key_is_not_bypassed_by_padding_whitespace(): void
    {
        $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['login' => 'login-test@ttu.edu.jo', 'password' => 'wrong']);
        }

        // محاولة سادسة بنفس القيمة لكن بمسافات إضافية — يجب أن تُحتسب ضمن نفس القفل
        $response = $this->post(route('login'), ['login' => '  login-test@ttu.edu.jo ', 'password' => 'wrong']);

        $response->assertSessionHasErrors('login');
        $message = $response->getSession()->get('errors')->first('login');
        $this->assertStringContainsString('محاولات', $message);
    }

    public function test_all_four_roles_can_log_in_with_email_or_identifier(): void
    {
        foreach (['student', 'staff', 'doctor', 'admin'] as $role) {
            $identifier = 'ident-'.$role;
            $user = User::factory()->create([
                'email' => $role.'-audit@ttu.edu.jo',
                'password' => Hash::make('password'),
                'role' => $role,
                'identifier' => $identifier,
            ]);

            $this->post(route('login'), ['login' => $user->email, 'password' => 'password']);
            $this->assertAuthenticatedAs($user);
            $this->post(route('logout'));

            $this->post(route('login'), ['login' => $identifier, 'password' => 'password']);
            $this->assertAuthenticatedAs($user);
            $this->post(route('logout'));
        }
    }
}
