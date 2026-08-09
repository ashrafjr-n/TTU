<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * أيام العمل الأسبوعية تُعيَّن الآن من صفحة إنشاء/تعديل حساب الدكتور فقط.
 * صفحة حضور الأطباء صارت للعرض فقط (لا فورم تعديل عليها).
 */
class DoctorScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => 'admin-schedule']);
    }

    private function doctor(?array $working = null): User
    {
        $doctor = User::factory()->create([
            'role' => 'doctor',
            'identifier' => fake()->unique()->numerify('###'),
        ]);

        if ($working !== null) {
            DoctorSchedule::create(['doctor_id' => $doctor->id, 'working_days' => $working]);
        }

        return $doctor;
    }

    // ------------------------------------------------------------------
    // الإنشاء: أيام العمل تُحفظ مع الحساب
    // ------------------------------------------------------------------

    public function test_creating_a_doctor_stores_the_selected_working_days(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.doctors.store'), [
            'name' => 'د. نور',
            'identifier' => '901',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'working_days' => ['0', '2', '4'],
        ]);

        $response->assertRedirect(route('admin.users'));

        $doctor = User::where('identifier', '901')->first();
        $this->assertNotNull($doctor);
        // قيم الـcheckbox تصل كنصوص — لازم تُخزَّن كأعداد صحيحة وإلا فشلت
        // مقارنة isWorkingOn() الصارمة
        $this->assertSame([0, 2, 4], $doctor->doctorSchedule->working_days);
    }

    public function test_creating_a_doctor_without_working_days_still_creates_an_empty_schedule(): void
    {
        $this->actingAs($this->admin())->post(route('admin.doctors.store'), [
            'name' => 'د. بلا جدول',
            'identifier' => '902',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $doctor = User::where('identifier', '902')->first();
        $this->assertNotNull($doctor->doctorSchedule);
        $this->assertSame([], $doctor->doctorSchedule->working_days);
    }

    public function test_an_invalid_working_day_is_rejected(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.doctors.store'), [
            'name' => 'د. خطأ',
            'identifier' => '903',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'working_days' => ['9'],
        ]);

        $response->assertSessionHasErrors('working_days.0');
        $this->assertDatabaseMissing('users', ['identifier' => '903']);
    }

    public function test_the_identifier_must_be_exactly_three_digits(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.doctors.store'), [
            'name' => 'د. رقم خطأ',
            'identifier' => '12',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertDatabaseMissing('users', ['name' => 'د. رقم خطأ']);
    }

    public function test_a_new_doctor_can_log_in_with_the_identifier_and_chosen_password(): void
    {
        $this->actingAs($this->admin())->post(route('admin.doctors.store'), [
            'name' => 'د. نور',
            'identifier' => '904',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->post(route('logout'));

        $response = $this->post(route('login'), ['login' => '904', 'password' => 'Password123!']);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    // ------------------------------------------------------------------
    // التعديل
    // ------------------------------------------------------------------

    public function test_the_edit_screen_renders_with_the_current_working_days(): void
    {
        $doctor = $this->doctor([1, 3]);

        $response = $this->actingAs($this->admin())->get(route('admin.doctors.edit', $doctor));

        $response->assertOk();
        $response->assertSee('أيام العمل الأسبوعية');
        $response->assertSee('value="1"', false);
    }

    public function test_updating_a_doctor_changes_name_identifier_and_working_days(): void
    {
        $doctor = $this->doctor([1, 3]);

        $response = $this->actingAs($this->admin())->put(route('admin.doctors.update', $doctor), [
            'name' => 'د. الاسم الجديد',
            'identifier' => '905',
            'working_days' => ['5', '6'],
        ]);

        $response->assertRedirect(route('admin.users'));

        $doctor->refresh();
        $this->assertSame('د. الاسم الجديد', $doctor->name);
        $this->assertSame('905', $doctor->identifier);
        $this->assertSame([5, 6], $doctor->doctorSchedule->working_days);
    }

    public function test_clearing_all_working_days_is_allowed(): void
    {
        $doctor = $this->doctor([1, 2, 3]);

        $this->actingAs($this->admin())->put(route('admin.doctors.update', $doctor), [
            'name' => $doctor->name,
            'identifier' => $doctor->identifier,
        ]);

        $this->assertSame([], $doctor->fresh()->doctorSchedule->working_days);
    }

    public function test_updating_a_doctor_without_an_existing_schedule_creates_one(): void
    {
        $doctor = $this->doctor();
        $this->assertNull($doctor->doctorSchedule);

        $this->actingAs($this->admin())->put(route('admin.doctors.update', $doctor), [
            'name' => $doctor->name,
            'identifier' => $doctor->identifier,
            'working_days' => ['2'],
        ]);

        $this->assertSame([2], $doctor->fresh()->doctorSchedule->working_days);
    }

    public function test_a_duplicate_identifier_is_rejected_but_the_doctors_own_identifier_is_allowed(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor([1]);
        User::factory()->create(['role' => 'doctor', 'identifier' => '777']);

        $this->actingAs($admin)->put(route('admin.doctors.update', $doctor), [
            'name' => $doctor->name,
            'identifier' => '777',
        ])->assertSessionHasErrors('identifier');

        // نفس رقمه الحالي يجب أن يُقبل (قاعدة unique تتجاهل صفّه)
        $this->actingAs($admin)->put(route('admin.doctors.update', $doctor), [
            'name' => 'اسم محدّث',
            'identifier' => $doctor->identifier,
        ])->assertSessionHasNoErrors();

        $this->assertSame('اسم محدّث', $doctor->fresh()->name);
    }

    public function test_the_edit_routes_reject_non_doctor_users(): void
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => '20212222']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.doctors.edit', $student))->assertNotFound();
        $this->actingAs($admin)->put(route('admin.doctors.update', $student), [
            'name' => 'x',
            'identifier' => '999',
        ])->assertNotFound();
    }

    public function test_a_non_admin_cannot_reach_the_doctor_edit_screen(): void
    {
        $doctor = $this->doctor([1]);

        $this->actingAs($doctor)->get(route('admin.doctors.edit', $doctor))->assertForbidden();
    }

    // ------------------------------------------------------------------
    // صفحة الحضور: عرض فقط
    // ------------------------------------------------------------------

    public function test_the_attendance_page_shows_the_schedule_read_only(): void
    {
        $this->doctor([1, 3]);

        $response = $this->actingAs($this->admin())->get(route('admin.attendance'));

        $response->assertOk();
        $response->assertSee('جدول عمل الأطباء الأسبوعي');
        // الأيام معروضة كنص، لا كـcheckbox قابل للتعديل
        $response->assertSee('الاثنين');
        $response->assertDontSee('name="working_days[]"', false);
        $response->assertDontSee('type="checkbox"', false);
    }

    public function test_the_attendance_page_links_to_the_edit_screen_instead(): void
    {
        $doctor = $this->doctor([1]);

        $response = $this->actingAs($this->admin())->get(route('admin.attendance'));

        $response->assertSee(route('admin.doctors.edit', $doctor), false);
    }

    public function test_a_doctor_with_no_schedule_is_shown_as_unassigned(): void
    {
        $this->doctor();

        $response = $this->actingAs($this->admin())->get(route('admin.attendance'));

        $response->assertSee('لم تُعيَّن أيام عمل');
    }

    public function test_the_old_schedule_update_route_no_longer_exists(): void
    {
        $this->assertFalse(
            app('router')->getRoutes()->hasNamedRoute('admin.doctors.schedule.update')
        );
    }
}
