<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * السجل الكامل للحجوزات (admin.booking-history): كل حجز أُنشئ بالنظام على
 * الإطلاق، مؤكدًا كان أو ملغى، بصرف النظر عن توزيع الأيام على الأطباء —
 * أداة إدارية فقط، منفصلة تمامًا عن لوحات الأطباء المُقيَّدة.
 */
class AdminBookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->safeEmail()]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function bookingOn(Carbon $date, User $user, string $status = 'confirmed', int $hour = 9, int $minute = 0): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'booking_date' => $date,
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => $status,
        ]);
    }

    public function test_admin_can_view_the_full_booking_history(): void
    {
        $student = $this->student();
        $this->bookingOn(Carbon::today(), $student);

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history'));

        $response->assertOk();
        $response->assertSee($student->name);
    }

    public function test_a_non_admin_cannot_view_the_booking_history(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);

        $this->actingAs($doctor)->get(route('admin.booking-history'))->assertForbidden();
    }

    public function test_history_includes_cancelled_bookings_unlike_doctor_facing_views(): void
    {
        $student = $this->student();
        $booking = $this->bookingOn(Carbon::today(), $student, 'cancelled');

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history'));

        $response->assertOk();
        $response->assertSee($student->name);
        $response->assertSee(__('admin_booking_history.table.status_cancelled'));
    }

    public function test_history_is_not_scoped_by_doctor_day_assignment(): void
    {
        // لا تعيين إطلاقًا لأي طبيب بأي يوم — ومع ذلك يظهر الحجز بسجل
        // الإدارة، بعكس لوحة أي دكتور (المقيَّدة بـDoctorDayAssignment)
        $student = $this->student();
        $this->bookingOn(Carbon::today(), $student);

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history'));

        $response->assertOk();
        $response->assertSee($student->name);
    }

    public function test_can_filter_by_date_range(): void
    {
        $inRange = $this->student();
        $outOfRange = $this->student();

        $this->bookingOn(Carbon::create(2026, 3, 10), $inRange);
        $this->bookingOn(Carbon::create(2026, 5, 1), $outOfRange);

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', [
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertSee($inRange->name);
        $response->assertDontSee($outOfRange->name);
    }

    public function test_can_filter_by_status(): void
    {
        $confirmedUser = $this->student();
        $cancelledUser = $this->student();

        $this->bookingOn(Carbon::today(), $confirmedUser, 'confirmed');
        $this->bookingOn(Carbon::today(), $cancelledUser, 'cancelled');

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', ['status' => 'cancelled']));

        $response->assertOk();
        $response->assertSee($cancelledUser->name);
        $response->assertDontSee($confirmedUser->name);
    }

    public function test_can_search_by_user_name_or_identifier(): void
    {
        $target = User::factory()->create([
            'role' => 'student',
            'name' => 'زكريا العتيبي',
            'identifier' => '99988877',
        ]);
        $other = $this->student();

        $this->bookingOn(Carbon::today(), $target, 'confirmed', 9, 0);
        $this->bookingOn(Carbon::today(), $other, 'confirmed', 9, 5);

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', ['search' => 'زكريا']));

        $response->assertOk();
        $response->assertSee($target->name);
        $response->assertDontSee($other->name);

        $byIdentifier = $this->actingAs($this->admin())->get(route('admin.booking-history', ['search' => '99988877']));
        $byIdentifier->assertSee($target->name);
    }

    public function test_the_empty_state_shows_when_no_bookings_match_the_filters(): void
    {
        $this->bookingOn(Carbon::today(), $this->student());

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', ['search' => 'no-such-user-xyz']));

        $response->assertOk();
        $response->assertSee(__('admin_booking_history.empty'));
    }
}
