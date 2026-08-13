<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DoctorDayAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * اسم الطبيب على مودال "لديك حجز حاليًا" — يُشتق من تعيينات الأيام
 * (DoctorDayAssignment) حسب يوم أسبوع تاريخ الحجز نفسه، لا من عمود على
 * bookings (لا وجود له). الاختبارات هنا تثبت أن الاسم المعروض يتبع تاريخ
 * الحجز تحديدًا (لا أول طبيب، ولا طبيب اليوم الحالي)، وأن يومًا بلا تعيين
 * يعرض نصًا بديلًا بدل الانكسار أو اسم خاطئ.
 */
class ActiveBookingDoctorNameTest extends TestCase
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

    private function doctor(string $name): User
    {
        return User::factory()->create([
            'role' => 'doctor',
            'name' => $name,
            'identifier' => fake()->unique()->numerify('###'),
        ]);
    }

    /**
     * يثبّت "الآن" على أحد أيام العيادة (الأحد) الساعة 8 صباحًا، فيصير:
     * اليوم = الأحد (dayOfWeek 0)، وغدًا = الاثنين (1) — وكلاهما ضمن نافذة
     * الحجز الثلاثية، فيمكن الحجز على أي منهما ضمن نفس الاختبار.
     */
    private function freezeOnSunday(): void
    {
        Carbon::setTestNow(Carbon::today()->next(Carbon::SUNDAY)->setTime(8, 0));
    }

    private function bookOn(User $user, Carbon $date, int $hour = 9, int $minute = 0): void
    {
        Booking::create([
            'user_id' => $user->id,
            'booking_date' => $date->toDateString(),
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    public function test_modal_shows_the_doctor_assigned_to_the_bookings_own_weekday(): void
    {
        $this->freezeOnSunday();
        $sundayDoctor = $this->doctor('د. ليلى منصور');
        $mondayDoctor = $this->doctor('د. سامي عبيدات');

        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $sundayDoctor->id]);
        DoctorDayAssignment::create(['day_of_week' => 1, 'doctor_id' => $mondayDoctor->id]);

        $user = $this->student();
        $this->bookOn($user, Carbon::today());

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('لديك حجز حاليًا');
        $response->assertSee('مع د. ليلى منصور');
        $response->assertDontSee('د. سامي عبيدات');
    }

    public function test_a_booking_on_another_day_shows_that_days_doctor_instead(): void
    {
        $this->freezeOnSunday();
        $sundayDoctor = $this->doctor('د. ليلى منصور');
        $mondayDoctor = $this->doctor('د. سامي عبيدات');

        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $sundayDoctor->id]);
        DoctorDayAssignment::create(['day_of_week' => 1, 'doctor_id' => $mondayDoctor->id]);

        $user = $this->student();
        // نفس "اليوم" الحالي (أحد) لكن الحجز على الغد (اثنين) — الاسم المعروض
        // يجب أن يتبع تاريخ الحجز، لا اليوم الجاري
        $this->bookOn($user, Carbon::today()->addDay());

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('مع د. سامي عبيدات');
        $response->assertDontSee('د. ليلى منصور');
    }

    public function test_an_unassigned_day_falls_back_to_the_unassigned_label(): void
    {
        $this->freezeOnSunday();
        // الأحد وحده مُعيَّن؛ الاثنين (تاريخ الحجز) بلا أي تعيين
        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $this->doctor('د. ليلى منصور')->id]);

        $user = $this->student();
        $this->bookOn($user, Carbon::today()->addDay());

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('لديك حجز حاليًا');
        $response->assertSee('طبيب غير محدد');
        $response->assertDontSee('د. ليلى منصور');
    }

    public function test_an_assignment_pointing_at_a_deleted_doctor_falls_back_too(): void
    {
        $this->freezeOnSunday();
        $doctor = $this->doctor('د. ليلى منصور');
        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $doctor->id]);
        $doctor->delete();

        $user = $this->student();
        $this->bookOn($user, Carbon::today());

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('طبيب غير محدد');
    }

    public function test_the_dashboard_modal_shows_the_doctor_name_as_well(): void
    {
        $this->freezeOnSunday();
        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $this->doctor('د. ليلى منصور')->id]);

        $user = $this->student();
        $this->bookOn($user, Carbon::today());

        $response = $this->actingAs($user)->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertSee('id="activeBookingModalOverlay"', false);
        $response->assertSee('مع د. ليلى منصور');
    }

    public function test_the_staff_dashboard_modal_shows_the_doctor_name_as_well(): void
    {
        $this->freezeOnSunday();
        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $this->doctor('د. ليلى منصور')->id]);

        $user = User::factory()->create(['role' => 'staff', 'identifier' => fake()->unique()->numerify('####')]);
        $this->bookOn($user, Carbon::today(), 9, 45);

        $response = $this->actingAs($user)->get(route('dashboard.staff'));

        $response->assertOk();
        $response->assertSee('مع د. ليلى منصور');
    }

    public function test_the_doctor_line_is_localized_in_english(): void
    {
        $this->freezeOnSunday();
        DoctorDayAssignment::create(['day_of_week' => 0, 'doctor_id' => $this->doctor('د. ليلى منصور')->id]);

        $user = $this->student();
        $this->bookOn($user, Carbon::today());

        $response = $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('You have an active booking');
        $response->assertSee('with د. ليلى منصور');
    }

    public function test_the_unassigned_fallback_is_localized_in_english(): void
    {
        $this->freezeOnSunday();

        $user = $this->student();
        $this->bookOn($user, Carbon::today());

        $response = $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('Doctor unassigned');
    }
}
