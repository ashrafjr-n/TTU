<?php

namespace Tests\Feature\Auth;

use App\Models\UniversityRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationAndLoginAuditTest extends TestCase
{
    use RefreshDatabase;

    private function seedStudentRecord(string $identifier = '20210123'): void
    {
        UniversityRecord::create(['identifier' => $identifier, 'type' => 'student', 'is_valid' => true]);
    }

    private function seedStaffRecord(string $identifier = '2320'): void
    {
        UniversityRecord::create(['identifier' => $identifier, 'type' => 'staff', 'is_valid' => true]);
    }

    private function validStudentPayload(array $overrides = []): array
    {
        return array_merge([
            'role' => 'student',
            'name' => 'طالب تجريبي',
            'identifier' => '20210123',
            'email' => 'newstudent@ttu.edu.jo',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_successful_student_registration_logs_in_and_redirects_to_student_dashboard(): void
    {
        $this->seedStudentRecord();

        $response = $this->post(route('register'), $this->validStudentPayload());

        $response->assertRedirect(route('dashboard.student'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'newstudent@ttu.edu.jo', 'role' => 'student']);
    }

    public function test_successful_staff_registration_logs_in_and_redirects_to_staff_dashboard(): void
    {
        $this->seedStaffRecord();

        $response = $this->post(route('register'), [
            'role' => 'staff',
            'name' => 'موظف تجريبي',
            'identifier' => '2320',
            'email' => 'newstaff@ttu.edu.jo',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard.staff'));
        $this->assertAuthenticated();
    }

    public function test_registration_regenerates_the_session_id(): void
    {
        $this->seedStudentRecord();

        $this->withSession([]);
        $oldId = $this->app['session']->getId();

        $this->post(route('register'), $this->validStudentPayload());

        $this->assertNotSame($oldId, $this->app['session']->getId());
    }

    public function test_duplicate_email_shows_specific_arabic_message(): void
    {
        $this->seedStudentRecord('20210456');
        User::factory()->create(['email' => 'taken@ttu.edu.jo', 'role' => 'student', 'identifier' => '11111111']);

        $response = $this->post(route('register'), $this->validStudentPayload([
            'identifier' => '20210456',
            'email' => 'taken@ttu.edu.jo',
        ]));

        $response->assertInvalid(['email' => 'هذا البريد الإلكتروني مستخدم بالفعل من قبل حساب آخر.']);
    }

    public function test_duplicate_identifier_shows_specific_arabic_message(): void
    {
        $this->seedStudentRecord();
        User::factory()->create(['role' => 'student', 'identifier' => '20210123']);

        $response = $this->post(route('register'), $this->validStudentPayload(['email' => 'another@ttu.edu.jo']));

        $response->assertInvalid(['identifier' => 'هذا الرقم الجامعي مستخدم بالفعل من قبل حساب آخر.']);
    }

    public function test_password_confirmation_mismatch_shows_arabic_message(): void
    {
        $this->seedStudentRecord();

        $response = $this->post(route('register'), $this->validStudentPayload([
            'password_confirmation' => 'different-password',
        ]));

        $response->assertInvalid(['password' => 'كلمتا المرور غير متطابقتين.']);
    }

    public function test_unclaimed_but_unrecorded_identifier_is_rejected_with_arabic_message(): void
    {
        // ما في سجل بالجامعة لهذا الرقم إطلاقًا
        $response = $this->post(route('register'), $this->validStudentPayload(['identifier' => '99999999']));

        $response->assertInvalid(['identifier' => 'الرقم المدخل غير موجود أو غير صحيح في سجلات الجامعة.']);
    }

    public function test_staff_identifier_cannot_be_used_to_register_as_student(): void
    {
        // موجود بالسجلات لكن كـ"موظف" لا "طالب"
        $this->seedStaffRecord('2320');

        $response = $this->post(route('register'), $this->validStudentPayload(['identifier' => '2320']));

        // digits:8 يرفضها فورًا لأنها 4 أرقام فقط
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_missing_required_fields_show_arabic_messages(): void
    {
        $response = $this->post(route('register'), ['role' => 'student']);

        $response->assertInvalid([
            'name' => 'حقل الاسم مطلوب.',
            'email' => 'حقل البريد الإلكتروني مطلوب.',
        ]);
    }

    // ------------------------------------------------------------------
    // Login audit
    // ------------------------------------------------------------------

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
