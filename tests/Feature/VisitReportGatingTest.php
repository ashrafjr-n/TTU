<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * لا معنى لتشخيص مريض قبل حضوره — يجب ألا يقدر الدكتور يرفق/يعدّل تقرير
 * زيارة لحجز لم يحن وقته بعد، سواء عبر الواجهة أو بطلب مباشر للمسار.
 */
class VisitReportGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->safeEmail()]);
    }

    private function patient(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function bookingAt(Carbon $date, int $hour, int $minute): Booking
    {
        return Booking::create([
            'user_id' => $this->patient()->id,
            'booking_date' => $date,
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    private function reportPayload(): array
    {
        return [
            'condition' => 'حرارة خفيفة',
            'examination' => 'فحص عام سليم',
        ];
    }

    public function test_cannot_attach_a_report_before_the_slot_start_time(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $booking = $this->bookingAt(Carbon::today(), 9, 30);

        $response = $this->actingAs($this->doctor())
            ->post(route('doctor.bookings.report.store', $booking), $this->reportPayload());

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('visit_reports', ['booking_id' => $booking->id]);
    }

    public function test_can_attach_a_report_once_the_slot_start_time_has_arrived(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 30));

        $booking = $this->bookingAt(Carbon::today(), 9, 30);

        $response = $this->actingAs($this->doctor())
            ->post(route('doctor.bookings.report.store', $booking), $this->reportPayload());

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('visit_reports', ['booking_id' => $booking->id]);
    }

    public function test_cannot_attach_a_report_for_tomorrows_booking(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $booking = $this->bookingAt(Carbon::tomorrow(), 9, 0);

        $response = $this->actingAs($this->doctor())
            ->post(route('doctor.bookings.report.store', $booking), $this->reportPayload());

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('visit_reports', ['booking_id' => $booking->id]);
    }

    public function test_can_still_edit_an_existing_report_for_a_past_booking(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $booking = $this->bookingAt(Carbon::today()->subDay(), 9, 0);
        $doctor = $this->doctor();

        VisitReport::create([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'condition' => 'قديم',
            'examination' => 'قديم',
        ]);

        $response = $this->actingAs($doctor)
            ->post(route('doctor.bookings.report.store', $booking), [
                'condition' => 'محدَّث',
                'examination' => 'محدَّث',
            ]);

        $response->assertSessionHas('success');
        $this->assertSame('محدَّث', VisitReport::where('booking_id', $booking->id)->first()->condition);
    }

    public function test_dashboard_hides_the_attach_button_for_bookings_that_have_not_started(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $this->bookingAt(Carbon::today(), 14, 0);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee(__('doctor.bookings_table.not_available_yet'));
        $response->assertDontSee(__('doctor.bookings_table.attach_report'));
    }

    public function test_dashboard_shows_the_attach_button_once_a_bookings_time_has_arrived(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 30));

        $this->bookingAt(Carbon::today(), 9, 0);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee(__('doctor.bookings_table.attach_report'));
    }

    public function test_dashboard_hides_the_attach_button_for_all_of_tomorrows_and_day_afters_bookings(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $this->bookingAt(Carbon::tomorrow(), 8, 0);
        $this->bookingAt(Carbon::today()->addDays(2), 8, 0);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertDontSee(__('doctor.bookings_table.attach_report'));
    }
}
