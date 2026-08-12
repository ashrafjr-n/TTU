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
 * صفحة "أدويتي": رسوم كل تقرير (0.20 د.أ × عدد الأدوية)، حالة فارغة أنيقة
 * عند عدم وجود تقارير، وترتيب التقارير من الأحدث للأقدم بحسب تاريخ/وقت
 * الموعد نفسه لا وقت إنشاء السجل.
 */
class MyMedicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->safeEmail()]);
    }

    private function bookingFor(User $user, Carbon $date, int $hour, int $minute = 0): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'booking_date' => $date->toDateString(),
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    private function reportFor(Booking $booking, User $doctor): VisitReport
    {
        return VisitReport::create([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'condition' => 'بخير',
            'examination' => 'فحص عام',
        ]);
    }

    public function test_the_empty_state_shows_when_the_student_has_no_reports(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->get(route('medications.mine'));

        $response->assertOk();
        $response->assertSee('لا توجد تقارير بعد');
        $response->assertSee('احجز موعدك الآن');
        $response->assertDontSee('الرسوم:', false);
    }

    public function test_a_report_with_medications_shows_the_calculated_fee(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $student = $this->student();
        $doctor = $this->doctor();
        $booking = $this->bookingFor($student, Carbon::today(), 9);
        $report = $this->reportFor($booking, $doctor);

        $panadol = Medication::create(['name_ar' => 'بنادول', 'name_en' => 'Panadol', 'stock_quantity' => 100, 'low_stock_threshold' => 10, 'is_active' => true]);
        $amoxicillin = Medication::create(['name_ar' => 'أموكسيسيلين', 'name_en' => 'Amoxicillin', 'stock_quantity' => 100, 'low_stock_threshold' => 10, 'is_active' => true]);

        $report->medications()->attach([
            $panadol->id => ['quantity' => 2],
            $amoxicillin->id => ['quantity' => 1],
        ]);

        $response = $this->actingAs($student)->get(route('medications.mine'));

        $response->assertOk();
        // دواءان في التقرير: 0.20 × 2 = 0.40 د.أ — بصرف النظر عن الكمية المصروفة لكل دواء
        $response->assertSee('0.20 د.أ');
        $response->assertSee('0.40 د.أ');
    }

    public function test_a_report_without_medications_shows_no_fee_badge(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $student = $this->student();
        $doctor = $this->doctor();
        $booking = $this->bookingFor($student, Carbon::today(), 9);
        $this->reportFor($booking, $doctor);

        $response = $this->actingAs($student)->get(route('medications.mine'));

        $response->assertOk();
        $response->assertSee('لم توصف أدوية لهذه الزيارة');
        $response->assertDontSee('الرسوم:', false);
    }

    public function test_visit_report_medications_fee_method_matches_the_expected_formula(): void
    {
        $doctor = $this->doctor();
        $student = $this->student();
        $booking = $this->bookingFor($student, Carbon::today(), 9);
        $report = $this->reportFor($booking, $doctor);

        $medications = collect(range(1, 3))->map(
            fn ($i) => Medication::create(['name_ar' => "دواء {$i}", 'name_en' => "Medication {$i}", 'stock_quantity' => 50, 'low_stock_threshold' => 5, 'is_active' => true])
        );

        $report->medications()->attach($medications->pluck('id')->mapWithKeys(fn ($id) => [$id => ['quantity' => 5]]));
        $report->load('medications');

        $this->assertEqualsWithDelta(0.60, $report->medicationsFee(), 0.001);
    }

    public function test_reports_are_sorted_newest_visit_first(): void
    {
        $student = $this->student();
        $doctor = $this->doctor();

        $oldBooking = $this->bookingFor($student, Carbon::today()->subDays(10), 9);
        $oldReport = $this->reportFor($oldBooking, $doctor);

        $recentBooking = $this->bookingFor($student, Carbon::today()->subDay(), 14);
        $recentReport = $this->reportFor($recentBooking, $doctor);

        $middleBooking = $this->bookingFor($student, Carbon::today()->subDays(5), 10);
        $middleReport = $this->reportFor($middleBooking, $doctor);

        $response = $this->actingAs($student)->get(route('medications.mine'));
        $response->assertOk();

        $content = $response->getContent();
        $recentPos = strpos($content, $recentBooking->booking_date->translatedFormat('d F Y'));
        $middlePos = strpos($content, $middleBooking->booking_date->translatedFormat('d F Y'));
        $oldPos = strpos($content, $oldBooking->booking_date->translatedFormat('d F Y'));

        $this->assertNotFalse($recentPos);
        $this->assertNotFalse($middlePos);
        $this->assertNotFalse($oldPos);
        $this->assertTrue($recentPos < $middlePos, 'أحدث زيارة يجب أن تظهر قبل الوسطى');
        $this->assertTrue($middlePos < $oldPos, 'الزيارة الوسطى يجب أن تظهر قبل الأقدم');
    }

    public function test_sort_uses_the_visit_date_not_the_report_creation_order(): void
    {
        // تقرير الزيارة الأقدم (تاريخ حجز أبعد) أُنشئ بعد تقرير الزيارة الأحدث
        // زمنيًا (created_at أحدث) — الترتيب الصحيح يعتمد على تاريخ/وقت الموعد
        // نفسه لا على created_at
        $student = $this->student();
        $doctor = $this->doctor();

        $recentBooking = $this->bookingFor($student, Carbon::today(), 9);
        $recentReport = $this->reportFor($recentBooking, $doctor);

        $olderBooking = $this->bookingFor($student, Carbon::today()->subDays(3), 9);
        $olderReport = $this->reportFor($olderBooking, $doctor);

        $response = $this->actingAs($student)->get(route('medications.mine'));
        $content = $response->getContent();

        $recentPos = strpos($content, $recentBooking->booking_date->translatedFormat('d F Y'));
        $olderPos = strpos($content, $olderBooking->booking_date->translatedFormat('d F Y'));

        $this->assertTrue($recentPos < $olderPos);
    }

    public function test_a_users_medications_page_never_shows_another_users_reports(): void
    {
        $student = $this->student();
        $otherStudent = $this->student();
        $doctor = $this->doctor();

        $booking = $this->bookingFor($otherStudent, Carbon::today(), 9);
        $this->reportFor($booking, $doctor);

        $response = $this->actingAs($student)->get(route('medications.mine'));

        $response->assertOk();
        $response->assertSee('لا توجد تقارير بعد');
    }
}
