<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingSemesterLimitTest extends TestCase
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

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'identifier' => fake()->unique()->numerify('####')]);
    }

    /**
     * حجز مؤكد مباشر (بدون المرور بـ store()) بتاريخ ماضٍ ضمن فصل معيّن —
     * لا يُعتبر "فعّالًا" أبدًا (booking_date < اليوم)، فيعزل اختبارات الحد
     * الفصلي عن بوابة "حجز فعّال واحد" المنفصلة تمامًا.
     */
    private function pastConfirmedBooking(User $user, string $date, int $hour = 9, int $minute = 0): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'booking_date' => $date,
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    // ------------------------------------------------------------------
    // منطق تحديد الفصل
    // ------------------------------------------------------------------

    public function test_semester_helper_classifies_dates_correctly(): void
    {
        $cases = [
            // الفصل الأول يمتد عبر حدود السنة (8 أكتوبر → 15 يناير التالي)
            ['2026-10-08', 'semester_1'],
            ['2026-12-25', 'semester_1'],
            ['2027-01-01', 'semester_1'],
            ['2027-01-15', 'semester_1'],
            // الفصل الثاني
            ['2027-02-17', 'semester_2'],
            ['2027-04-01', 'semester_2'],
            ['2027-06-15', 'semester_2'],
            // الصيفي
            ['2027-07-11', 'summer'],
            ['2027-08-15', 'summer'],
            ['2027-09-20', 'summer'],
            // فجوات بين الفصول — لا فصل نشط
            ['2027-01-16', null],
            ['2027-02-16', null],
            ['2027-06-16', null],
            ['2027-07-10', null],
            ['2027-09-21', null],
            ['2026-10-07', null],
        ];

        foreach ($cases as [$dateString, $expectedKey]) {
            $semester = Booking::semesterFor(Carbon::createFromFormat('Y-m-d', $dateString));

            if ($expectedKey === null) {
                $this->assertNull($semester, "$dateString يجب ألا يقع ضمن أي فصل");
            } else {
                $this->assertNotNull($semester, "$dateString يجب أن يقع ضمن فصل");
                $this->assertSame($expectedKey, $semester['key'], "$dateString يجب أن يقع ضمن $expectedKey");
            }
        }
    }

    // ------------------------------------------------------------------
    // الحد الفصلي (3 حجوزات مؤكدة كحد أقصى)
    // ------------------------------------------------------------------

    public function test_booking_within_the_limit_succeeds(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 15, 8, 0));
        $user = $this->student();

        $this->pastConfirmedBooking($user, '2026-10-10');
        $this->pastConfirmedBooking($user, '2026-10-15');

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('success');
        $this->assertSame(3, Booking::where('user_id', $user->id)->where('status', 'confirmed')->count());
    }

    public function test_fourth_booking_in_a_semester_is_blocked(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 0));
        $user = $this->student();

        $this->pastConfirmedBooking($user, '2026-10-10');
        $this->pastConfirmedBooking($user, '2026-10-15');
        $this->pastConfirmedBooking($user, '2026-10-20');

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('error');
        $this->assertSame(
            __('booking.errors.semester_limit_reached'),
            session('error'),
        );
        $this->assertSame(3, Booking::where('user_id', $user->id)->where('status', 'confirmed')->count());
        $this->assertDatabaseMissing('bookings', [
            'user_id' => $user->id,
            'booking_date' => Carbon::today()->toDateString(),
            'booking_hour' => 9,
            'booking_minute' => 0,
        ]);
    }

    public function test_index_shows_semester_limit_modal_instead_of_slot_grid_when_limit_reached(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 0));
        $user = $this->student();

        $this->pastConfirmedBooking($user, '2026-10-10');
        $this->pastConfirmedBooking($user, '2026-10-15');
        $this->pastConfirmedBooking($user, '2026-10-20');

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertStatus(200);
        $response->assertSee('بلغت الحد الأقصى لحجوزات هذا الفصل');
        $response->assertSee(route('contact'), false);
        $response->assertDontSee('id="bookModalOverlay"', false);
    }

    /**
     * نافذة الحجز (اليوم + يومين قادمين) قد تعبر حدود فصلين قرب نهايته —
     * لو "اليوم" محظور لكن يوم لاحق بالنافذة غير محظور (هنا: فجوة بلا فصل
     * نشط)، يجب أن تبقى شبكة الأوقات ظاهرة بدل إخفاء الصفحة كليًا خلف مودال
     * الحد الفصلي، لأن ذلك اليوم اللاحق قابل للحجز فعليًا عبر store().
     */
    public function test_index_still_shows_slot_grid_when_only_some_window_days_are_limited(): void
    {
        // 14 يناير: آخر يومين بالفصل الأول (ينتهي 15 يناير). النافذة تمتد
        // لـ16 يناير أيضًا — وهو فجوة بلا فصل نشط (لا حد إطلاقًا).
        Carbon::setTestNow(Carbon::create(2027, 1, 14, 8, 0));
        $user = $this->student();

        $this->pastConfirmedBooking($user, '2026-10-10');
        $this->pastConfirmedBooking($user, '2026-10-15');
        $this->pastConfirmedBooking($user, '2026-10-20');

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertStatus(200);
        $response->assertSee('id="bookModalOverlay"', false);
        $response->assertDontSee('بلغت الحد الأقصى لحجوزات هذا الفصل');

        // اليوم نفسه (14 يناير) لا يزال ضمن الفصل الأول المحظور فعليًا
        $blocked = $this->actingAs($user)->post(route('booking.store'), [
            'date' => '2027-01-14', 'hour' => 9, 'minute' => 0,
        ]);
        $blocked->assertSessionHas('error');
        $this->assertSame(__('booking.errors.semester_limit_reached'), session('error'));

        // 16 يناير — فجوة بلا فصل نشط — يبقى قابلًا للحجز رغم الحد الفصلي
        $allowed = $this->actingAs($user)->post(route('booking.store'), [
            'date' => '2027-01-16', 'hour' => 9, 'minute' => 0,
        ]);
        $allowed->assertSessionHas('success');
    }

    public function test_cancelling_a_booking_frees_up_a_semester_slot(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 0));
        $user = $this->student();

        $this->pastConfirmedBooking($user, '2026-10-10');
        $this->pastConfirmedBooking($user, '2026-10-15');
        $third = $this->pastConfirmedBooking($user, '2026-10-20');

        // بدون إلغاء، الحجز الرابع محظور
        $blocked = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $blocked->assertSessionHas('error');

        // إلغاء واحد من الثلاثة يُحرر خانة فورًا — الملغى لا يُحتسب أصلًا
        $this->actingAs($user)->delete(route('booking.destroy', $third));

        $allowed = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $allowed->assertSessionHas('success');

        $this->assertSame(3, Booking::where('user_id', $user->id)->where('status', 'confirmed')->count());
        $this->assertSame(1, Booking::where('user_id', $user->id)->where('status', 'cancelled')->count());
    }

    public function test_booking_in_a_different_semester_does_not_count_against_the_limit(): void
    {
        $user = $this->student();

        // ثلاثة حجوزات مؤكدة بالفصل الأول (أكتوبر 2026)
        $this->pastConfirmedBooking($user, '2026-10-10');
        $this->pastConfirmedBooking($user, '2026-10-15');
        $this->pastConfirmedBooking($user, '2026-10-20');

        // محاولة حجز بالفصل الثاني (مارس 2027) — فصل مختلف تمامًا
        Carbon::setTestNow(Carbon::create(2027, 3, 1, 8, 0));

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('success');
        $this->assertSame(4, Booking::where('user_id', $user->id)->where('status', 'confirmed')->count());
    }

    public function test_booking_outside_any_semester_is_not_limited(): void
    {
        // 20 يناير — فجوة بين الفصل الأول والثاني، لا فصل نشط
        Carbon::setTestNow(Carbon::create(2027, 1, 20, 8, 0));
        $user = $this->student();

        $this->pastConfirmedBooking($user, '2026-10-10');
        $this->pastConfirmedBooking($user, '2026-10-11');
        $this->pastConfirmedBooking($user, '2026-10-12');
        $this->pastConfirmedBooking($user, '2026-10-13');
        $this->pastConfirmedBooking($user, '2026-10-14');

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('success');
    }

    public function test_staff_member_is_also_limited_to_three_bookings_per_semester(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 0));
        $user = $this->staff();

        $this->pastConfirmedBooking($user, '2026-10-10', 9, 45);
        $this->pastConfirmedBooking($user, '2026-10-15', 9, 50);
        $this->pastConfirmedBooking($user, '2026-10-20', 9, 55);

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 10, 'minute' => 45]);

        $response->assertSessionHas('error');
        $this->assertSame(__('booking.errors.semester_limit_reached'), session('error'));
        $this->assertSame(3, Booking::where('user_id', $user->id)->where('status', 'confirmed')->count());
    }
}
