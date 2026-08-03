<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingSlotTest extends TestCase
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

    public function test_student_can_book_a_student_slot(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $response = $this->actingAs($this->student())
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', ['booking_hour' => 9, 'booking_minute' => 0, 'status' => 'confirmed']);
    }

    public function test_student_cannot_book_an_unreleased_staff_slot(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $response = $this->actingAs($this->student())
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('bookings', ['booking_hour' => 9, 'booking_minute' => 45]);
    }

    public function test_student_can_book_a_staff_slot_once_its_start_time_has_passed_unbooked(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 46));

        $response = $this->actingAs($this->student())
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', ['booking_hour' => 9, 'booking_minute' => 45, 'status' => 'confirmed']);
    }

    public function test_staff_cannot_book_a_student_slot(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $response = $this->actingAs($this->staff())
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('bookings', ['booking_hour' => 9, 'booking_minute' => 0]);
    }

    public function test_staff_can_book_their_own_slot(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $response = $this->actingAs($this->staff())
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', ['booking_hour' => 9, 'booking_minute' => 45, 'status' => 'confirmed']);
    }

    public function test_double_booking_the_same_slot_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $first = $this->student();
        $second = $this->student();

        $this->actingAs($first)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $response = $this->actingAs($second)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('error');
        $this->assertSame(1, Booking::where('booking_hour', 9)->where('booking_minute', 0)->where('status', 'confirmed')->count());
    }

    public function test_cancelling_a_booking_frees_the_slot(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $booking = Booking::where('user_id', $user->id)->first();
        $this->actingAs($user)->delete(route('booking.destroy', $booking));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);

        $other = $this->student();
        $response = $this->actingAs($other)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $response->assertSessionHas('success');
    }

    public function test_already_passed_slot_cannot_be_booked(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 30));

        $response = $this->actingAs($this->student())
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('bookings', ['booking_hour' => 9, 'booking_minute' => 0]);
    }
}
