<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * الحالة الثلاثية المعروضة للحجز (Booking::displayStatus): مؤكد (قادم) →
 * منتهي بلا توثيق (فضي) → منتهي وموثَّق بتقرير (أخضر)، بمعزل تام عن الملغى
 * (أحمر، غير متأثر بالوقت أو بوجود تقرير). القيمة محسوبة من status + وقت
 * الموعد + وجود visit_reports، بلا عمود مخزَّن — هذا ما تتحقق منه هذه
 * الاختبارات تحديدًا.
 */
class BookingDisplayStatusTest extends TestCase
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
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
    }

    private function bookingAt(Carbon $slotStart, User $user, string $status = 'confirmed'): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'booking_date' => $slotStart->copy()->startOfDay(),
            'booking_hour' => $slotStart->hour,
            'booking_minute' => $slotStart->minute,
            'price' => Booking::PRICE,
            'status' => $status,
        ]);
    }

    public function test_upcoming_confirmed_booking_displays_as_confirmed(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $booking = $this->bookingAt(Carbon::today()->setTime(9, 30), $this->student());

        $this->assertSame('confirmed', $booking->displayStatus());
    }

    public function test_started_booking_without_visit_report_displays_as_ended_undocumented(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 5));

        // بدأ منذ 5 دقائق ولا تقرير له — لا فرق هنا بين "لم يحضر" و"حضر ولم
        // يُسجَّل تقريره بعد"، كلاهما نفس الحالة المعروضة
        $booking = $this->bookingAt(Carbon::today()->setTime(10, 0), $this->student());

        $this->assertSame('ended_undocumented', $booking->displayStatus());
    }

    public function test_started_booking_with_visit_report_displays_as_ended_documented(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 5));

        $doctor = $this->doctor();
        $booking = $this->bookingAt(Carbon::today()->setTime(10, 0), $this->student());

        VisitReport::create([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'condition' => 'بخير',
            'examination' => 'فحص عادي',
        ]);

        $this->assertSame('ended_documented', $booking->fresh('visitReport')->displayStatus());
    }

    public function test_cancelled_booking_displays_as_cancelled_regardless_of_time_or_report(): void
    {
        // ملغى لكن وقته لم يحن بعد — يجب أن يبقى "ملغى" لا "مؤكد"
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $upcomingCancelled = $this->bookingAt(Carbon::today()->setTime(9, 30), $this->student(), 'cancelled');
        $this->assertSame('cancelled', $upcomingCancelled->displayStatus());

        // ملغى وأيضًا وقته ماضٍ — يبقى "ملغى" لا "منتهي"
        $pastCancelled = $this->bookingAt(Carbon::today()->setTime(8, 30), $this->student(), 'cancelled');
        $this->assertSame('cancelled', $pastCancelled->displayStatus());
    }

    public function test_student_dashboard_shows_ended_undocumented_badge_for_a_past_unreported_visit(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(11, 0));

        $student = $this->student();
        $this->bookingAt(Carbon::today()->setTime(9, 0), $student);

        $response = $this->actingAs($student)->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertSee(__('common.booking_status.ended_undocumented'));
    }

    public function test_staff_dashboard_shows_confirmed_badge_for_an_upcoming_visit(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $staff = User::factory()->create(['role' => 'staff', 'identifier' => fake()->unique()->numerify('####')]);
        $this->bookingAt(Carbon::today()->setTime(9, 0), $staff);

        $response = $this->actingAs($staff)->get(route('dashboard.staff'));

        $response->assertOk();
        $response->assertSee(__('common.booking_status.confirmed'));
    }

    public function test_admin_booking_history_distinguishes_documented_from_undocumented_past_visits(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(11, 0));

        $admin = User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->safeEmail()]);
        $doctor = $this->doctor();

        $documented = $this->bookingAt(Carbon::today()->setTime(9, 0), $this->student());
        VisitReport::create([
            'booking_id' => $documented->id,
            'doctor_id' => $doctor->id,
            'condition' => 'بخير',
            'examination' => 'فحص عادي',
        ]);

        $this->bookingAt(Carbon::today()->setTime(9, 5), $this->student());

        $response = $this->actingAs($admin)->get(route('admin.booking-history'));

        $response->assertOk();
        $response->assertSee(__('common.booking_status.ended_undocumented'));
        // كلا الحجزين نصهما "منتهي" بالعربية (نفس التسمية، لونان مختلفان) —
        // التحقق من التمييز الفعلي بينهما (فضي/موثَّق أخضر) عبر لون الشارة
        // بالـHTML مباشرة بدل __() المتطابقة نصيًا
        $response->assertSee('bg-gray-100', false);
        $response->assertSee('bg-green-50', false);
    }
}
