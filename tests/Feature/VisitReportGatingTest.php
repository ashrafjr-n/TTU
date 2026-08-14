<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DoctorDayAssignment;
use App\Models\User;
use App\Models\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * لا معنى لتشخيص مريض قبل حضوره — يجب ألا يقدر الدكتور يرفق/يعدّل تقرير
 * زيارة لحجز لم يحن وقته بعد، سواء عبر الواجهة أو بطلب مباشر للمسار.
 *
 * "اليوم" هنا أحد ثابت (راجع today()) والدكتور الفاعل مُعيَّن له صراحة —
 * وإلا رفض VisitReportController::store الطلب بـ403 (يوم الحجز غير مُعيَّن
 * لهذا الدكتور) قبل أن تُفحص شروط التوقيت أصلًا.
 */
class VisitReportGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** أحد ثابت — يبقى نفس اليوم بصرف النظر عن يوم تشغيل الاختبارات الفعلي */
    private function today(): Carbon
    {
        return Carbon::create(2026, 8, 16);
    }

    private function doctor(): User
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);

        // مُعيَّن لكل أيام الأسبوع الخمسة (أحد–خميس) — يبسّط اختبارات هذا
        // الملف (يوم "اليوم" وأيام "غدًا"/"بعد الغد" ضمن هذا الأسبوع نفسه)
        // دون الحاجة لتعيين يوم بيوم.
        foreach (DoctorDayAssignment::CLINIC_DAYS as $dayOfWeek) {
            DoctorDayAssignment::create(['day_of_week' => $dayOfWeek, 'doctor_id' => $doctor->id]);
        }

        return $doctor;
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
        Carbon::setTestNow($this->today()->copy()->setTime(9, 0));

        $booking = $this->bookingAt($this->today(), 9, 30);

        $response = $this->actingAs($this->doctor())
            ->post(route('doctor.bookings.report.store', $booking), $this->reportPayload());

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('visit_reports', ['booking_id' => $booking->id]);
    }

    public function test_can_attach_a_report_once_the_slot_start_time_has_arrived(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(9, 30));

        $booking = $this->bookingAt($this->today(), 9, 30);

        $response = $this->actingAs($this->doctor())
            ->post(route('doctor.bookings.report.store', $booking), $this->reportPayload());

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('visit_reports', ['booking_id' => $booking->id]);
    }

    public function test_cannot_attach_a_report_for_tomorrows_booking(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(9, 0));

        $booking = $this->bookingAt($this->today()->copy()->addDay(), 9, 0);

        $response = $this->actingAs($this->doctor())
            ->post(route('doctor.bookings.report.store', $booking), $this->reportPayload());

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('visit_reports', ['booking_id' => $booking->id]);
    }

    public function test_can_still_edit_an_existing_report_for_a_past_booking(): void
    {
        // "أمس" هنا يوم اثنين (لا سبت) عمدًا — السبت ليس من أيام دوام العيادة
        // (Sun-Thu)، فلا يُعيَّن لأي دكتور إطلاقًا مهما اتسع تعيينه؛ اختبار
        // "حجز بالأمس" يحتاج يومًا سابقًا يقع فعليًا ضمن أيام العيادة.
        Carbon::setTestNow($this->today()->copy()->addDay()->setTime(9, 0));
        $booking = $this->bookingAt($this->today(), 9, 0);
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
        Carbon::setTestNow($this->today()->copy()->setTime(9, 0));

        $this->bookingAt($this->today(), 14, 0);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee(__('doctor.bookings_table.not_available_yet'));
        $response->assertDontSee(__('doctor.bookings_table.attach_report'));
    }

    public function test_dashboard_shows_the_attach_button_once_a_bookings_time_has_arrived(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(9, 30));

        $this->bookingAt($this->today(), 9, 0);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee(__('doctor.bookings_table.attach_report'));
    }

    public function test_dashboard_hides_the_attach_button_for_all_of_tomorrows_and_day_afters_bookings(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(9, 0));

        $this->bookingAt($this->today()->copy()->addDay(), 8, 0);
        $this->bookingAt($this->today()->copy()->addDays(2), 8, 0);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertDontSee(__('doctor.bookings_table.attach_report'));
    }

    /**
     * قائمة اختيار الدواء بمودال تقرير الزيارة كانت تُغلق فورًا تقريبًا بعد
     * فتحها: x-on:click.outside كانت على لوحة النتائج وحدها (شقيقة لحقل
     * البحث، لا أب له)، فأي نقرة على الحقل نفسه لفتحها تُحتسب "نقرة خارجية"
     * فتُغلقها فورًا. هذا الاختبار يقفل البنية المُصحَّحة (الخاصية على العنصر
     * الأب الذي يضم الحقل ولوحة النتائج معًا) كي لا يعود أحد يفصلهما لاحقًا
     * بلا انتباه لهذا التفاعل بين focus وclick.outside.
     */
    public function test_medication_dropdown_outside_click_guard_wraps_the_input_not_just_the_results_panel(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(9, 30));

        $this->bookingAt($this->today(), 9, 0);

        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString(
            'class="flex-1 min-w-0 relative" x-on:click.outside="row.open = false"',
            $html
        );
        // النمط القديم المعطوب: click.outside على لوحة النتائج (x-show) نفسها
        $this->assertStringNotContainsString(
            'x-show="row.open" x-on:click.outside="row.open = false"',
            $html
        );
    }

    public function test_a_doctor_cannot_attach_a_report_for_a_booking_on_another_doctors_day(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(9, 30));

        $owningDoctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
        DoctorDayAssignment::create(['day_of_week' => $this->today()->dayOfWeek, 'doctor_id' => $owningDoctor->id]);

        $otherDoctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
        // otherDoctor غير مُعيَّن ليوم "اليوم" إطلاقًا

        $booking = $this->bookingAt($this->today(), 9, 0);

        $response = $this->actingAs($otherDoctor)
            ->post(route('doctor.bookings.report.store', $booking), $this->reportPayload());

        $response->assertForbidden();
        $this->assertDatabaseMissing('visit_reports', ['booking_id' => $booking->id]);
    }
}
