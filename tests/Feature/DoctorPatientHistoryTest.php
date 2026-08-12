<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Medication;
use App\Models\User;
use App\Models\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * تاريخ زيارات المريض السابقة (قراءة فقط) يظهر بجانب فورم التقرير — يساعد
 * الدكتور يعرف حالة المريض السابقة قبل ما يكتب تقرير اليوم.
 */
class DoctorPatientHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
    }

    private function patient(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function bookingAt(User $patient, Carbon $date, int $hour = 9, int $minute = 0): Booking
    {
        return Booking::create([
            'user_id' => $patient->id,
            'booking_date' => $date,
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    private function reportFor(Booking $booking, User $doctor, array $overrides = []): VisitReport
    {
        return VisitReport::create(array_merge([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'condition' => 'حرارة خفيفة',
            'examination' => 'فحص عام سليم',
        ], $overrides));
    }

    /**
     * يبحث عن حجز معيّن ضمن بنية $days المُرسَلة للعرض، ويرجع
     * patientHistory الملحقة به.
     */
    private function historyFor($days, Booking $booking): array
    {
        foreach ($days as $day) {
            $found = $day['bookings']->firstWhere('id', $booking->id);
            if ($found) {
                return $found->patientHistory;
            }
        }

        $this->fail("Booking {$booking->id} not found in \$days");
    }

    public function test_a_first_time_patient_has_no_history(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $patient = $this->patient();
        $booking = $this->bookingAt($patient, Carbon::today());

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('days', function ($days) use ($booking) {
            $this->assertSame([], $this->historyFor($days, $booking));

            return true;
        });
    }

    public function test_a_returning_patients_prior_visit_shows_in_history(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $patient = $this->patient();
        $doctor = $this->doctor();

        $panadol = Medication::create(['name_ar' => 'بانادول', 'name_en' => 'Panadol', 'stock_quantity' => 50, 'low_stock_threshold' => 10]);

        $pastBooking = $this->bookingAt($patient, Carbon::today()->subDays(3));
        $pastReport = $this->reportFor($pastBooking, $doctor, [
            'condition' => 'صداع',
            'diagnosis' => 'إجهاد',
        ]);
        $pastReport->medications()->attach($panadol->id, ['quantity' => 2]);

        $todayBooking = $this->bookingAt($patient, Carbon::today());

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('days', function ($days) use ($todayBooking) {
            $history = $this->historyFor($days, $todayBooking);

            $this->assertCount(1, $history);
            $this->assertSame('صداع', $history[0]['condition']);
            $this->assertSame('إجهاد', $history[0]['diagnosis']);
            $this->assertSame(['بانادول'], $history[0]['medications']);

            return true;
        });
    }

    public function test_a_bookings_own_report_is_not_listed_in_its_own_history(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $patient = $this->patient();
        $doctor = $this->doctor();

        $booking = $this->bookingAt($patient, Carbon::today());
        $this->reportFor($booking, $doctor);

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('days', function ($days) use ($booking) {
            $this->assertSame([], $this->historyFor($days, $booking));

            return true;
        });
    }

    public function test_history_is_sorted_most_recent_visit_first(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $patient = $this->patient();
        $doctor = $this->doctor();

        $oldest = $this->bookingAt($patient, Carbon::today()->subDays(10));
        $this->reportFor($oldest, $doctor, ['condition' => 'الأقدم']);

        $newer = $this->bookingAt($patient, Carbon::today()->subDays(2));
        $this->reportFor($newer, $doctor, ['condition' => 'الأحدث']);

        $todayBooking = $this->bookingAt($patient, Carbon::today());

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('days', function ($days) use ($todayBooking) {
            $history = $this->historyFor($days, $todayBooking);

            $this->assertCount(2, $history);
            $this->assertSame('الأحدث', $history[0]['condition']);
            $this->assertSame('الأقدم', $history[1]['condition']);

            return true;
        });
    }

    public function test_history_only_includes_this_patients_own_visits(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $doctor = $this->doctor();

        $otherPatient = $this->patient();
        $otherBooking = $this->bookingAt($otherPatient, Carbon::today()->subDay());
        $this->reportFor($otherBooking, $doctor, ['condition' => 'مريض آخر']);

        $patient = $this->patient();
        $todayBooking = $this->bookingAt($patient, Carbon::today());

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertViewHas('days', function ($days) use ($todayBooking) {
            $this->assertSame([], $this->historyFor($days, $todayBooking));

            return true;
        });
    }
}
