<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DoctorDayAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * لوحة الدكتور صارت أسبوعية (سبت→جمعة، راجع Booking::currentWeekDates)
 * ومقتصرة على أيامه المُعيَّنة تحديدًا (DoctorDayAssignment) — لا نافذة 3
 * أيام قادمة كصفحة الحجز، ولا كل الحجوزات بصرف النظر عن الدكتور كما كان
 * الحال سابقًا.
 */
class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** أحد ثابت — يبقى نفس اليوم بصرف النظر عن يوم تشغيل الاختبارات الفعلي */
    private function sunday(): Carbon
    {
        return Carbon::create(2026, 8, 16);
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
    }

    private function assignDay(User $doctor, Carbon $date): void
    {
        DoctorDayAssignment::create(['day_of_week' => $date->dayOfWeek, 'doctor_id' => $doctor->id]);
    }

    private function patient(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function bookingOn(Carbon $date, User $patient): Booking
    {
        return Booking::create([
            'user_id' => $patient->id,
            'booking_date' => $date,
            'booking_hour' => 9,
            'booking_minute' => 0,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    public function test_dashboard_renders_for_today_by_default(): void
    {
        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
    }

    public function test_a_doctor_with_no_assigned_days_sees_the_empty_state(): void
    {
        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee(__('doctor.no_assigned_days'));
    }

    public function test_dashboard_shows_a_tab_for_each_assigned_day_in_the_current_week(): void
    {
        Carbon::setTestNow($this->sunday()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignDay($doctor, $this->sunday());
        $this->assignDay($doctor, $this->sunday()->copy()->addDay()); // الاثنين

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee(__('common.days')[0]); // الأحد
        $response->assertSee(__('common.days')[1]); // الاثنين
        // الثلاثاء غير مُعيَّن لهذا الدكتور — لا يظهر كتبويب إطلاقًا
        $response->assertDontSee(__('common.days')[2]);
    }

    public function test_dashboard_lists_bookings_for_all_assigned_days_in_the_current_week(): void
    {
        Carbon::setTestNow($this->sunday()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        foreach (DoctorDayAssignment::CLINIC_DAYS as $dayOfWeek) {
            $this->assignDay($doctor, $this->sunday()->copy()->addDays($dayOfWeek));
        }

        $sundayPatient = $this->patient();
        $thursdayPatient = $this->patient();

        $this->bookingOn($this->sunday(), $sundayPatient);
        $this->bookingOn($this->sunday()->copy()->addDays(4), $thursdayPatient);

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        // الحجوزان يُرسَلان معًا بالصفحة (لكل تبويب لوحته الخاصة المخفية
        // بـCSS)، وليس فقط حجز اليوم — هذا ما يسمح بالتبديل بين الأيام من
        // غير إعادة تحميل الصفحة.
        $response->assertOk();
        $response->assertSee($sundayPatient->name);
        $response->assertSee($thursdayPatient->name);
    }

    public function test_dashboard_does_not_show_bookings_on_a_day_assigned_to_another_doctor(): void
    {
        Carbon::setTestNow($this->sunday()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignDay($doctor, $this->sunday());

        $otherDoctor = $this->doctor();
        $tuesday = $this->sunday()->copy()->addDays(2);
        $this->assignDay($otherDoctor, $tuesday);

        $otherDoctorsPatient = $this->patient();
        $this->bookingOn($tuesday, $otherDoctorsPatient);

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertDontSee($otherDoctorsPatient->name);
    }

    public function test_dashboard_does_not_show_bookings_outside_the_current_week(): void
    {
        Carbon::setTestNow($this->sunday()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        foreach (DoctorDayAssignment::CLINIC_DAYS as $dayOfWeek) {
            $this->assignDay($doctor, $this->sunday()->copy()->addDays($dayOfWeek));
        }

        // نفس يوم الأسبوع (أحد) لكن الأسبوع التالي — خارج نافذة "الأسبوع
        // الحالي" رغم أنه يوم مُعيَّن للدكتور من حيث المبدأ
        $nextWeekPatient = $this->patient();
        $this->bookingOn($this->sunday()->copy()->addWeek(), $nextWeekPatient);

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertDontSee($nextWeekPatient->name);
    }

    public function test_dashboard_shows_past_days_of_the_current_week_when_opened_later_in_the_week(): void
    {
        // اليوم أربعاء — لكن حجز الأحد (بداية نفس الأسبوع) لازم يظل ظاهرًا،
        // فالأسبوع كامل (ماضٍ وقادم) يُعرض دائمًا، لا القادم فقط.
        $wednesday = $this->sunday()->copy()->addDays(3);
        Carbon::setTestNow($wednesday->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        foreach (DoctorDayAssignment::CLINIC_DAYS as $dayOfWeek) {
            $this->assignDay($doctor, $this->sunday()->copy()->addDays($dayOfWeek));
        }

        $sundayPatient = $this->patient();
        $this->bookingOn($this->sunday(), $sundayPatient);

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee($sundayPatient->name);
    }

    public function test_the_week_resets_on_saturday(): void
    {
        // السبت هو أول يوم بعد إغلاق العيادة الأسبوعي (خميس آخر يوم عمل) —
        // فتح اللوحة يوم السبت يجب أن يعرض الأسبوع "القادم" (بدءًا من هذا
        // السبت نفسه)، لا الأسبوع المنتهي للتو.
        $saturday = $this->sunday()->copy()->subDay(); // السبت السابق لهذا الأحد
        Carbon::setTestNow($saturday->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignDay($doctor, $this->sunday()); // أحد الأسبوع الجديد (بعد هذا السبت)

        $newWeekPatient = $this->patient();
        $this->bookingOn($this->sunday(), $newWeekPatient);

        // حجز بأحد الأسبوع "المنتهي" (قبل هذا السبت بأسبوع) يجب ألا يظهر
        $oldWeekPatient = $this->patient();
        $this->bookingOn($this->sunday()->copy()->subWeek(), $oldWeekPatient);

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee($newWeekPatient->name);
        $response->assertDontSee($oldWeekPatient->name);
    }

    public function test_todays_bookings_stat_only_counts_today_regardless_of_other_days(): void
    {
        Carbon::setTestNow($this->sunday()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        foreach (DoctorDayAssignment::CLINIC_DAYS as $dayOfWeek) {
            $this->assignDay($doctor, $this->sunday()->copy()->addDays($dayOfWeek));
        }

        $this->bookingOn($this->sunday(), $this->patient());
        $this->bookingOn($this->sunday()->copy()->addDay(), $this->patient());
        $this->bookingOn($this->sunday()->copy()->addDays(2), $this->patient());

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('todayBookingsCount', 1);
    }

    public function test_todays_bookings_stat_is_zero_when_today_is_not_an_assigned_day(): void
    {
        Carbon::setTestNow($this->sunday()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        // مُعيَّن للاثنين فقط — لا الأحد ("اليوم")
        $this->assignDay($doctor, $this->sunday()->copy()->addDay());

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('todayBookingsCount', 0);
    }

    public function test_an_unrecognized_date_query_is_ignored_instead_of_crashing(): void
    {
        // النافذة أسبوعية ثابتة (لا تُقرأ من الرابط) — أي قيمة زائدة بالرابط
        // يجب تُتجاهل بدل ما تكسر الصفحة.
        $response = $this->actingAs($this->doctor())
            ->get(route('dashboard.doctor', ['date' => 'not-a-date']));

        $response->assertOk();
    }
}
