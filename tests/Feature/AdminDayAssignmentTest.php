<?php

namespace Tests\Feature;

use App\Models\DoctorDayAssignment;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحة "توزيع الأيام على الأطباء" (admin.day-assignments): من يملك أي يوم
 * من أيام دوام العيادة (أحد–خميس)، وقدرة المدير على إعادة تعيين يوم لطبيب
 * آخر (مثلًا لو الطبيب الحالي غائب) — مصدر الحقيقة الوحيد لملكية الحجوزات
 * بلوحات الأطباء (DoctorController/VisitReportController).
 */
class AdminDayAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->safeEmail()]);
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
    }

    public function test_admin_can_view_the_day_assignments_page(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.day-assignments'));

        $response->assertOk();
        $response->assertSee(__('admin_day_assignments.heading'));
    }

    public function test_a_non_admin_cannot_view_the_page(): void
    {
        $this->actingAs($this->doctor())->get(route('admin.day-assignments'))->assertForbidden();
    }

    public function test_admin_can_assign_a_day_to_a_doctor(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor();

        $response = $this->actingAs($admin)->post(route('admin.day-assignments.update'), [
            'day_of_week' => 0,
            'doctor_id' => $doctor->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('doctor_day_assignments', [
            'day_of_week' => 0,
            'doctor_id' => $doctor->id,
        ]);
    }

    public function test_reassigning_a_day_replaces_the_previous_doctor(): void
    {
        $admin = $this->admin();
        $firstDoctor = $this->doctor();
        $secondDoctor = $this->doctor();

        DoctorDayAssignment::create(['day_of_week' => 2, 'doctor_id' => $firstDoctor->id]);

        $this->actingAs($admin)->post(route('admin.day-assignments.update'), [
            'day_of_week' => 2,
            'doctor_id' => $secondDoctor->id,
        ]);

        // يوم واحد فريد (unique) بالجدول — صف واحد فقط ليوم الثلاثاء، محدَّث
        // للطبيب الجديد لا صف إضافي
        $this->assertSame(1, DoctorDayAssignment::where('day_of_week', 2)->count());
        $this->assertSame($secondDoctor->id, DoctorDayAssignment::where('day_of_week', 2)->first()->doctor_id);
    }

    public function test_a_day_outside_sunday_through_thursday_is_rejected(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor();

        // الجمعة (5) ليست من أيام دوام العيادة القابلة للتعيين
        $response = $this->actingAs($admin)->post(route('admin.day-assignments.update'), [
            'day_of_week' => 5,
            'doctor_id' => $doctor->id,
        ]);

        $response->assertSessionHasErrors('day_of_week');
        $this->assertDatabaseMissing('doctor_day_assignments', ['day_of_week' => 5]);
    }

    public function test_assigning_a_day_to_a_non_doctor_account_is_rejected(): void
    {
        $admin = $this->admin();
        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        $this->actingAs($admin)->post(route('admin.day-assignments.update'), [
            'day_of_week' => 0,
            'doctor_id' => $student->id,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('doctor_day_assignments', ['day_of_week' => 0]);
    }

    public function test_a_mismatch_warning_shows_when_the_assigned_day_is_not_in_the_doctors_working_days(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor();
        DoctorSchedule::create(['doctor_id' => $doctor->id, 'working_days' => [1, 2]]); // اثنين وثلاثاء فقط

        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $doctor->id]); // مُعيَّن للأحد رغم أنه لا يعمل به

        $response = $this->actingAs($admin)->get(route('admin.day-assignments'));

        $response->assertOk();
        $response->assertSee(__('admin_day_assignments.mismatch_warning'));
    }

    public function test_no_mismatch_warning_when_the_assigned_day_matches_working_days(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor();
        DoctorSchedule::create(['doctor_id' => $doctor->id, 'working_days' => [0, 1]]);

        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $doctor->id]);

        $response = $this->actingAs($admin)->get(route('admin.day-assignments'));

        $response->assertOk();
        $response->assertDontSee(__('admin_day_assignments.mismatch_warning'));
    }

    public function test_an_activity_log_entry_is_written_on_reassignment(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor();

        $this->actingAs($admin)->post(route('admin.day-assignments.update'), [
            'day_of_week' => 4,
            'doctor_id' => $doctor->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'day_assignment_updated',
        ]);
    }
}
