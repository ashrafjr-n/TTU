<?php

namespace Tests\Feature;

use App\Models\DoctorAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * الحضور صار يُسجَّل تلقائيًا لحظة تسجيل دخول الدكتور — لا يوجد زر يدوي.
 * الانصراف بقي يدويًا، وشبكة الأمان (انصراف تلقائي 4 عصرًا) بقيت كما هي.
 */
class DoctorAutoCheckInTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function doctor(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'doctor',
            'identifier' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attributes));
    }

    private function login(User $user, string $password = 'password')
    {
        return $this->post(route('login'), ['login' => $user->email, 'password' => $password]);
    }

    public function test_logging_in_registers_todays_attendance_automatically(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 15));
        $doctor = $this->doctor();

        $this->login($doctor)->assertRedirect(route('dashboard'));

        $attendance = DoctorAttendance::where('doctor_id', $doctor->id)->first();

        $this->assertNotNull($attendance);
        $this->assertSame(Carbon::today()->toDateString(), $attendance->date->toDateString());
        $this->assertSame('09:15:00', $attendance->check_in_at->format('H:i:s'));
        $this->assertNull($attendance->check_out_at);
        $this->assertFalse($attendance->is_auto_checkout);
    }

    public function test_auto_check_in_is_recorded_in_the_activity_log(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();

        $this->login($doctor);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $doctor->id,
            'action' => 'doctor_check_in',
        ]);
    }

    public function test_the_doctor_shows_as_on_duty_to_the_admin_immediately_after_login(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor(['name' => 'د. تجريبي']);

        $this->login($doctor);

        $admin = User::factory()->create(['role' => 'admin', 'identifier' => 'admin-auto-checkin']);
        $response = $this->actingAs($admin)->get(route('admin.attendance'));

        $response->assertOk();
        $response->assertSee('على رأس العمل الآن');
    }

    public function test_logging_in_again_the_same_day_does_not_reset_the_check_in_time(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();
        $this->login($doctor);

        $this->post(route('logout'));

        // دخول ثانٍ بعد ساعتين — وقت الحضور الأصلي يجب أن يبقى كما هو، والدخول
        // نفسه يجب أن ينجح (صف الحضور الموجود يجب أن يُقرأ لا أن يُعاد إدراجه
        // فيضرب قيد unique ويُفشل تسجيل الدخول كله)
        Carbon::setTestNow(Carbon::today()->setTime(11, 0));
        $this->login($doctor)->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($doctor);

        $this->assertSame(1, DoctorAttendance::where('doctor_id', $doctor->id)->count());
        $this->assertSame(
            '09:00:00',
            DoctorAttendance::where('doctor_id', $doctor->id)->first()->check_in_at->format('H:i:s')
        );
    }

    public function test_logging_in_after_checking_out_does_not_reopen_the_shift(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();
        $this->login($doctor);

        Carbon::setTestNow(Carbon::today()->setTime(13, 0));
        $this->actingAs($doctor)->post(route('doctor.attendance.checkout'));

        $this->post(route('logout'));

        Carbon::setTestNow(Carbon::today()->setTime(14, 0));
        $this->login($doctor)->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($doctor);

        $attendance = DoctorAttendance::where('doctor_id', $doctor->id)->first();
        $this->assertSame(1, DoctorAttendance::where('doctor_id', $doctor->id)->count());
        $this->assertSame('13:00:00', $attendance->check_out_at->format('H:i:s'));
    }

    public function test_a_new_day_gets_its_own_attendance_row(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();
        $firstDay = Carbon::today()->toDateString();
        $secondDay = Carbon::tomorrow()->toDateString();

        $this->login($doctor);
        $this->post(route('logout'));

        Carbon::setTestNow(Carbon::tomorrow()->setTime(9, 30));
        $this->login($doctor);

        $dates = DoctorAttendance::where('doctor_id', $doctor->id)
            ->get()
            ->map(fn (DoctorAttendance $a) => $a->date->toDateString())
            ->sort()
            ->values()
            ->all();

        $this->assertSame([$firstDay, $secondDay], $dates);
    }

    public function test_a_disabled_doctor_does_not_get_an_attendance_row(): void
    {
        // Auth::attempt ينجح ويطلق حدث Login قبل فحص is_active في LoginRequest،
        // لذلك المستمع نفسه لازم يتجاهل الحسابات المعطّلة
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor(['is_active' => false]);

        $this->login($doctor)->assertSessionHasErrors('login');

        $this->assertDatabaseCount('doctor_attendance', 0);
        $this->assertGuest();
    }

    public function test_a_failed_login_does_not_create_an_attendance_row(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();

        $this->login($doctor, 'wrong-password')->assertSessionHasErrors('login');

        $this->assertDatabaseCount('doctor_attendance', 0);
    }

    public function test_non_doctor_logins_never_create_attendance_rows(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $student = User::factory()->create([
            'role' => 'student',
            'identifier' => '20210999',
            'password' => Hash::make('password'),
        ]);

        $this->login($student);

        $this->assertDatabaseCount('doctor_attendance', 0);
    }

    public function test_the_manual_check_in_route_no_longer_exists(): void
    {
        $this->assertFalse(
            app('router')->getRoutes()->hasNamedRoute('doctor.attendance.checkin')
        );
    }

    public function test_the_dashboard_shows_no_check_in_button_but_keeps_check_out(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();
        $this->login($doctor);

        $response = $this->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertDontSee('تسجيل الحضور');
        $response->assertSee('تسجيل الانصراف');
        $response->assertSee('متواجد الآن');
    }

    public function test_manual_check_out_still_works_after_auto_check_in(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();
        $this->login($doctor);

        Carbon::setTestNow(Carbon::today()->setTime(12, 30));
        $this->post(route('doctor.attendance.checkout'))->assertSessionHas('success');

        $attendance = DoctorAttendance::where('doctor_id', $doctor->id)->first();
        $this->assertSame('12:30:00', $attendance->check_out_at->format('H:i:s'));
        $this->assertFalse($attendance->is_auto_checkout);
    }

    public function test_the_4pm_auto_checkout_safety_net_still_closes_an_auto_checked_in_shift(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $doctor = $this->doctor();
        $this->login($doctor);

        // الدكتور نسي يسجّل انصرافه — المهمة المجدولة 4 عصرًا تغلق الدوام
        Carbon::setTestNow(Carbon::today()->setTime(16, 0));
        $this->artisan('attendance:auto-checkout')->assertExitCode(0);

        $attendance = DoctorAttendance::where('doctor_id', $doctor->id)->first();
        $this->assertSame('16:00:00', $attendance->check_out_at->format('H:i:s'));
        $this->assertTrue($attendance->is_auto_checkout);
    }
}
