<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
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

    // ------------------------------------------------------------------
    // فلتر الأسبوع — بديل مدى التواريخ الحر (و"الحالة") السابقين: كل خيار
    // أسبوع كامل من السبت إلى الجمعة، بنفس عُرف الأسبوع المستخدم بلوحة
    // الدكتور (Booking::currentWeekDates) وصفحة الحجز.
    // ------------------------------------------------------------------

    /** يثبّت "اليوم" على الأربعاء 19 أغسطس 2026 — أسبوعه: السبت 15 → الجمعة 21 */
    private function freezeMidWeek(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 19, 10, 0));
    }

    public function test_can_filter_by_the_current_week(): void
    {
        $this->freezeMidWeek();
        $thisWeek = $this->student();
        $lastWeek = $this->student();

        $this->bookingOn(Carbon::create(2026, 8, 17), $thisWeek);  // اثنين — هذا الأسبوع
        $this->bookingOn(Carbon::create(2026, 8, 12), $lastWeek);  // أربعاء — الأسبوع الماضي

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', ['week' => 0]));

        $response->assertOk();
        $response->assertSee($thisWeek->name);
        $response->assertDontSee($lastWeek->name);
    }

    public function test_can_filter_by_a_previous_week(): void
    {
        $this->freezeMidWeek();
        $thisWeek = $this->student();
        $twoWeeksAgo = $this->student();

        $this->bookingOn(Carbon::create(2026, 8, 17), $thisWeek);
        $this->bookingOn(Carbon::create(2026, 8, 4), $twoWeeksAgo); // ثلاثاء — قبل أسبوعين

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', ['week' => 2]));

        $response->assertOk();
        $response->assertSee($twoWeeksAgo->name);
        $response->assertDontSee($thisWeek->name);
    }

    public function test_the_week_filter_covers_the_full_saturday_to_friday_range_inclusive(): void
    {
        $this->freezeMidWeek();
        $saturday = $this->student();
        $friday = $this->student();
        $justBefore = $this->student();
        $justAfter = $this->student();

        $this->bookingOn(Carbon::create(2026, 8, 15), $saturday);   // أول يوم بالأسبوع
        $this->bookingOn(Carbon::create(2026, 8, 21), $friday);     // آخر يوم بالأسبوع
        $this->bookingOn(Carbon::create(2026, 8, 14), $justBefore); // جمعة الأسبوع السابق
        $this->bookingOn(Carbon::create(2026, 8, 22), $justAfter);  // سبت الأسبوع التالي

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', ['week' => 0]));

        $response->assertOk();
        $response->assertSee($saturday->name);
        $response->assertSee($friday->name);
        $response->assertDontSee($justBefore->name);
        $response->assertDontSee($justAfter->name);
    }

    public function test_no_week_selected_shows_every_week(): void
    {
        $this->freezeMidWeek();
        $thisWeek = $this->student();
        $longAgo = $this->student();

        $this->bookingOn(Carbon::create(2026, 8, 17), $thisWeek);
        $this->bookingOn(Carbon::create(2025, 1, 6), $longAgo);

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history'));

        $response->assertOk();
        $response->assertSee($thisWeek->name);
        $response->assertSee($longAgo->name);
    }

    public function test_the_week_filter_offers_the_recent_weeks_and_an_all_weeks_option(): void
    {
        $this->freezeMidWeek();

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history'));

        $response->assertOk();
        $response->assertSee(__('admin_booking_history.filters.week_label'));
        $response->assertSee(__('admin_booking_history.filters.week_all'));
        $response->assertSee(__('admin_booking_history.filters.week_current'));
        $response->assertSee(__('admin_booking_history.filters.week_last'));
        $response->assertSee(__('admin_booking_history.filters.week_two_ago'));
        // أقدم أسبوع بالقائمة موجود، وما بعده غير معروض
        $response->assertSee('value="'.(AdminController::HISTORY_WEEKS - 1).'"', false);
        $response->assertDontSee('value="'.AdminController::HISTORY_WEEKS.'"', false);
    }

    public function test_the_selected_week_stays_selected_in_the_dropdown(): void
    {
        $this->freezeMidWeek();

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history', ['week' => 0]));

        $response->assertOk();
        // "هذا الأسبوع" قيمته 0، وكان لا بد ألا تُعامل كقيمة فارغة
        $response->assertSee('value="0" selected', false);
        $response->assertSee(__('admin_booking_history.filters.clear'));
    }

    public function test_an_out_of_range_week_is_rejected(): void
    {
        $this->freezeMidWeek();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.booking-history', ['week' => AdminController::HISTORY_WEEKS]));

        $response->assertSessionHasErrors('week');
    }

    public function test_the_removed_status_and_date_range_filters_are_gone(): void
    {
        $this->freezeMidWeek();
        $confirmedUser = $this->student();
        $cancelledUser = $this->student();

        $this->bookingOn(Carbon::create(2026, 8, 17), $confirmedUser, 'confirmed');
        $this->bookingOn(Carbon::create(2026, 8, 17), $cancelledUser, 'cancelled', 9, 5);

        $response = $this->actingAs($this->admin())->get(route('admin.booking-history'));

        $response->assertOk();
        $response->assertDontSee('name="status"', false);
        $response->assertDontSee('name="from"', false);
        $response->assertDontSee('name="to"', false);

        // ولو أُرسلت المعاملات القديمة يدويًا فلا أثر لها إطلاقًا — الحالتان تظهران
        $ignored = $this->actingAs($this->admin())->get(route('admin.booking-history', [
            'status' => 'cancelled', 'from' => '2020-01-01', 'to' => '2020-12-31',
        ]));

        $ignored->assertOk();
        $ignored->assertSee($confirmedUser->name);
        $ignored->assertSee($cancelledUser->name);
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
