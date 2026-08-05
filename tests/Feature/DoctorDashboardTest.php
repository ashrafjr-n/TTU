<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => 'doctor-audit']);
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

    public function test_dashboard_shows_the_three_day_tabs(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee(__('booking.day.today'));
        $response->assertSee(__('booking.day.tomorrow'));
        $response->assertSee(__('booking.day.day_after'));
    }

    public function test_dashboard_lists_bookings_for_all_three_days_in_the_window(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $today = $this->patient();
        $tomorrow = $this->patient();
        $dayAfter = $this->patient();

        $this->bookingOn(Carbon::today(), $today);
        $this->bookingOn(Carbon::tomorrow(), $tomorrow);
        $this->bookingOn(Carbon::today()->addDays(2), $dayAfter);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        // الحجوزات الثلاثة تُرسَل جميعها بالصفحة (لكل تبويب لوحته الخاصة
        // المخفية بـCSS)، وليس فقط حجز اليوم — هذا ما يسمح بالتبديل بين
        // الأيام من غير إعادة تحميل الصفحة.
        $response->assertOk();
        $response->assertSee($today->name);
        $response->assertSee($tomorrow->name);
        $response->assertSee($dayAfter->name);
    }

    public function test_dashboard_does_not_show_bookings_outside_the_three_day_window(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $farPatient = $this->patient();
        $this->bookingOn(Carbon::today()->addDays(5), $farPatient);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertDontSee($farPatient->name);
    }

    public function test_todays_bookings_stat_only_counts_today_regardless_of_other_days(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $this->bookingOn(Carbon::today(), $this->patient());
        $this->bookingOn(Carbon::tomorrow(), $this->patient());
        $this->bookingOn(Carbon::today()->addDays(2), $this->patient());

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('todayBookingsCount', 1);
    }

    public function test_an_unrecognized_date_query_is_ignored_instead_of_crashing(): void
    {
        // النافذة صارت ثابتة (اليوم + يومين قادمين) ولا تُقرأ من الرابط —
        // أي قيمة زائدة بالرابط يجب تُتجاهل بدل ما تكسر الصفحة.
        $response = $this->actingAs($this->doctor())
            ->get(route('dashboard.doctor', ['date' => 'not-a-date']));

        $response->assertOk();
    }
}
