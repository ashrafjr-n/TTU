<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingSingleActiveTest extends TestCase
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

    public function test_user_cannot_hold_two_active_bookings_at_once(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();

        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 10, 'minute' => 5]);

        $response->assertSessionHas('error');
        $this->assertSame(
            1,
            Booking::where('user_id', $user->id)->where('status', 'confirmed')->count()
        );
        $this->assertDatabaseMissing('bookings', ['user_id' => $user->id, 'booking_hour' => 10, 'booking_minute' => 5]);
    }

    public function test_a_past_booking_does_not_block_a_new_one(): void
    {
        $user = $this->student();

        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        // الوقت الآن تجاوز نهاية خانة 9:00 (9:05) — يجب ألا تُعتبر "فعّالة" بعد الآن
        Carbon::setTestNow(Carbon::today()->setTime(9, 10));
        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 10, 'minute' => 5]);

        $response->assertSessionHas('success');
        $this->assertSame(2, Booking::where('user_id', $user->id)->count());
    }

    public function test_cancelling_the_active_booking_allows_a_new_one(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();

        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $booking = Booking::where('user_id', $user->id)->first();

        $this->actingAs($user)->delete(route('booking.destroy', $booking));

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 10, 'minute' => 5]);
        $response->assertSessionHas('success');
    }

    public function test_successful_booking_redirects_to_role_dashboard(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertRedirect(route('dashboard.student'));
    }

    public function test_index_shows_active_booking_modal_instead_of_slot_grid(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertStatus(200);
        $response->assertSee('لديك حجز حاليًا');
        $response->assertSee('9:00 صباحًا');
        $response->assertDontSee('id="bookModalOverlay"', false);
    }

    public function test_index_shows_slot_grid_when_no_active_booking(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertStatus(200);
        $response->assertSee('id="bookModalOverlay"', false);
        $response->assertDontSee('لديك حجز حاليًا');
    }

    public function test_cancelling_from_the_active_booking_modal_redirects_to_booking_index(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $booking = Booking::where('user_id', $user->id)->first();

        $response = $this->actingAs($user)->delete(route('booking.destroy', $booking));

        $response->assertRedirect(route('booking.index'));
    }

    // ------------------------------------------------------------------
    // خانة موظف محررة تُحجز من طالب — الحجز يُنشأ بعد أن يبدأ وقته الأصلي
    // فعليًا (وأحيانًا بعد انتهائه)، لذلك isUpcoming() العادية (بالاعتماد
    // على نافذة الـ5 دقائق الأصلية) لا تصلح لتحديد "هل الحجز فعّال؟" هنا.
    // ------------------------------------------------------------------

    public function test_a_released_slot_booking_still_gates_the_booking_page(): void
    {
        // 9:45 خانة موظف — تتحرر للطلاب فور مرور 9:45، ونحجزها الساعة 9:46
        // أي أن نافذتها الأصلية (تنتهي 9:50) تكاد تكون منتهية أصلًا وقت الحجز
        Carbon::setTestNow(Carbon::today()->setTime(9, 46));
        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);
        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'booking_hour' => 9, 'booking_minute' => 45]);

        // نتقدم بالوقت لما بعد نهاية النافذة الأصلية للخانة (9:50) بوقت طويل —
        // الحجز يجب أن يبقى "فعّالًا" ويمنع حجزًا جديدًا رغم ذلك
        Carbon::setTestNow(Carbon::today()->setTime(11, 0));

        $response = $this->actingAs($user)->get(route('booking.index'));
        $response->assertSee('لديك حجز حاليًا');
        $response->assertSee('9:45 صباحًا');

        $storeResponse = $this->actingAs($user)->post(route('booking.store'), ['hour' => 12, 'minute' => 30]);
        $storeResponse->assertSessionHas('error');
        $this->assertDatabaseMissing('bookings', ['user_id' => $user->id, 'booking_hour' => 12, 'booking_minute' => 30]);
    }

    public function test_cancelling_a_released_slot_booking_restores_the_slot_grid(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 46));
        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);
        $booking = Booking::where('user_id', $user->id)->first();

        Carbon::setTestNow(Carbon::today()->setTime(11, 0));

        $cancelResponse = $this->actingAs($user)->delete(route('booking.destroy', $booking));
        $cancelResponse->assertRedirect(route('booking.index'));

        $indexResponse = $this->actingAs($user)->get(route('booking.index'));
        $indexResponse->assertSee('id="bookModalOverlay"', false);
        $indexResponse->assertDontSee('لديك حجز حاليًا');
    }

    public function test_staff_own_released_hour_slot_still_uses_normal_expiry(): void
    {
        // الموظف يحجز خانته العادية (ليست عبر آلية التحرر) — قاعدة isUpcoming
        // الأصلية يجب أن تبقى سارية عليه (تنتهي بانتهاء نافذتها الخاصة)
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $staff = User::factory()->create(['role' => 'staff', 'identifier' => fake()->unique()->numerify('####')]);
        $this->actingAs($staff)->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);

        Carbon::setTestNow(Carbon::today()->setTime(9, 51));

        $response = $this->actingAs($staff)->get(route('booking.index'));
        $response->assertDontSee('لديك حجز حاليًا');
    }
}
