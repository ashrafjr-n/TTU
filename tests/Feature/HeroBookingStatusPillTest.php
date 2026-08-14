<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * شارة حالة الحجز بهيرو الصفحة الرئيسية تُحسب من نفس القاعدة التي تحكم
 * Booking::bookableDates() (Booking::isBookingWindowClosed) — لا فحص وقت
 * منفصل. هذه الاختبارات تغطي حدود النافذة نفسها: الخميس قبل/عند 4 عصرًا،
 * الجمعة، والسبت (مفتوح رغم كونه يوم عطلة، بخلاف الخميس/الجمعة).
 *
 * التواريخ المرجعية (أغسطس 2026): 15 سبت، 16 أحد، 20 خميس، 21 جمعة —
 * نفس مرجع BookingWindowWeekdayTest.
 */
class HeroBookingStatusPillTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function assertPillOpen(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('home.hero.status_value_open'));
        $response->assertDontSee(__('home.hero.status_value_closed'));
        $response->assertSee('bg-green-500', false);
        $response->assertDontSee('bg-red-500', false);
    }

    private function assertPillClosed(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('home.hero.status_value_closed'));
        $response->assertDontSee(__('home.hero.status_value_open'));
        $response->assertSee('bg-red-500', false);
        $response->assertDontSee('bg-green-500', false);
    }

    public function test_pill_is_open_on_a_regular_weekday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-16')->setTime(10, 0)); // أحد
        $this->assertPillOpen();
    }

    public function test_pill_is_open_on_thursday_before_close_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(15, 59));
        $this->assertPillOpen();
    }

    public function test_pill_is_closed_on_thursday_at_close_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(16, 0));
        $this->assertPillClosed();
    }

    public function test_pill_stays_closed_all_through_friday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21')->setTime(0, 1));
        $this->assertPillClosed();

        Carbon::setTestNow(Carbon::parse('2026-08-21')->setTime(23, 59));
        $this->assertPillClosed();
    }

    public function test_pill_reopens_on_saturday_since_booking_reopens_for_sunday_monday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15')->setTime(8, 0));
        $this->assertPillOpen();
    }
}
