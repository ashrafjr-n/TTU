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
}
