<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardActiveBookingTest extends TestCase
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

    public function test_student_dashboard_links_to_booking_page_when_no_active_booking(): void
    {
        $user = $this->student();

        $response = $this->actingAs($user)->get(route('dashboard.student'));

        $response->assertStatus(200);
        $response->assertSee(route('booking.index'), false);
        $response->assertDontSee('لديك حجز حاليًا');
        $response->assertDontSee('id="activeBookingModalOverlay"', false);
    }

    public function test_student_dashboard_shows_blocking_modal_in_place_when_active_booking_exists(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response = $this->actingAs($user)->get(route('dashboard.student'));

        $response->assertStatus(200);
        $response->assertSee('لديك حجز حاليًا');
        $response->assertSee('9:00 صباحًا');
        $response->assertSee('id="activeBookingModalOverlay"', false);
        // الزر يفتح المودال في مكانه — لا رابط مباشر لصفحة الحجز على هذه البطاقة
        $response->assertSee('onclick="openActiveBookingModal()"', false);
    }

    public function test_cancelling_from_the_dashboard_modal_redirects_to_booking_index_with_slot_picker(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);
        $booking = Booking::where('user_id', $user->id)->first();

        // نفس فورم الإلغاء الموجود داخل مودال الداشبورد
        $cancelResponse = $this->actingAs($user)->delete(route('booking.destroy', $booking));
        $cancelResponse->assertRedirect(route('booking.index'));

        $indexResponse = $this->actingAs($user)->get(route('booking.index'));
        $indexResponse->assertSee('id="bookModalOverlay"', false);
        $indexResponse->assertDontSee('لديك حجز حاليًا');

        // ولوحة التحكم بعدها ترجع للرابط الطبيعي أيضًا
        $dashboardResponse = $this->actingAs($user)->get(route('dashboard.student'));
        $dashboardResponse->assertSee(route('booking.index'), false);
        $dashboardResponse->assertDontSee('لديك حجز حاليًا');
    }

    public function test_staff_dashboard_shows_blocking_modal_in_place_when_active_booking_exists(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->staff();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);

        $response = $this->actingAs($user)->get(route('dashboard.staff'));

        $response->assertStatus(200);
        $response->assertSee('لديك حجز حاليًا');
        $response->assertSee('9:45 صباحًا');
        $response->assertSee('id="activeBookingModalOverlay"', false);
    }

    public function test_staff_dashboard_links_to_booking_page_when_no_active_booking(): void
    {
        $user = $this->staff();

        $response = $this->actingAs($user)->get(route('dashboard.staff'));

        $response->assertStatus(200);
        $response->assertSee(route('booking.index'), false);
        $response->assertDontSee('لديك حجز حاليًا');
    }

    public function test_guest_is_redirected_to_login_instead_of_crashing_on_student_dashboard(): void
    {
        $response = $this->get(route('dashboard.student'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_instead_of_crashing_on_staff_dashboard(): void
    {
        $response = $this->get(route('dashboard.staff'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_access_the_student_dashboard(): void
    {
        $response = $this->actingAs($this->staff())->get(route('dashboard.student'));

        $response->assertForbidden();
    }

    public function test_student_cannot_access_the_staff_dashboard(): void
    {
        $response = $this->actingAs($this->student())->get(route('dashboard.staff'));

        $response->assertForbidden();
    }

    public function test_direct_navigation_to_booking_page_still_gates_as_backstop(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = $this->student();
        $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        // حتى لو المستخدم فتح /booking مباشرة (مو عن طريق زر الداشبورد)، البوابة تبقى فعّالة
        $response = $this->actingAs($user)->get(route('booking.index'));
        $response->assertSee('لديك حجز حاليًا');
        $response->assertDontSee('id="bookModalOverlay"', false);
    }
}
