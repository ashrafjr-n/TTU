<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * إشعار التأكيد المنبثق بعد نجاح الحجز — الحجز يُحوِّل المستخدم إلى لوحته،
 * وهي لا تعرض شريط 'success' الداخلي، فالتأكيد المرئي الوحيد هو هذا الإشعار
 * (مفتاح الجلسة 'toast' الذي يعرضه <x-flash-toast/> بالتخطيط العام).
 */
class BookingSuccessToastTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * "اليوم" مثبّت على أحد أيام دوام العيادة (الأحد 16 أغسطس 2026) بدل اليوم
     * الفعلي: نافذة الحجز صارت محصورة بأيام الدوام (أحد–خميس) وبنهاية الأسبوع،
     * فاختبار يعتمد على اليوم الحقيقي كان سيفشل كلما صادف تشغيله جمعة أو سبتًا
     * (نافذة فارغة)، أو خميسًا لو حجز "غدًا". نفس التاريخ المثبّت المستخدم في
     * DoctorCancelBookingTest/DoctorDashboardTest.
     */
    private function today(): Carbon
    {
        return Carbon::create(2026, 8, 16);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'identifier' => fake()->unique()->numerify('####')]);
    }

    public function test_successful_booking_flashes_a_toast_with_the_appointment_date_and_time(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));
        $user = $this->student();

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('toast');

        $toast = session('toast');
        $this->assertSame('success', $toast['type']);
        $this->assertSame(__('booking.toast.title'), $toast['title']);
        $this->assertStringContainsString('9:00 صباحًا', $toast['message']);
        $this->assertStringContainsString($this->today()->copy()->translatedFormat('d F Y'), $toast['message']);
    }

    public function test_the_toast_is_rendered_on_the_dashboard_after_a_real_booking(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));
        $user = $this->student();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertOk();
        $response->assertSee('id="flashToast"', false);
        $response->assertSee(__('booking.toast.title'));
        $response->assertSee('9:00 صباحًا');
    }

    public function test_the_toast_is_rendered_for_staff_too(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));
        $user = $this->staff();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 45]);

        $response->assertOk();
        $response->assertSee('id="flashToast"', false);
        $response->assertSee('9:45 صباحًا');
    }

    public function test_the_toast_is_localized_in_english(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));
        $user = $this->student();

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->followingRedirects()
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertOk();
        $response->assertSee('Booking confirmed');
        $response->assertSee('9:00 AM');
        $response->assertDontSee('تم تأكيد حجزك');
    }

    public function test_no_toast_is_rendered_without_a_booking(): void
    {
        $response = $this->actingAs($this->student())->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertDontSee('id="flashToast"', false);
    }

    public function test_a_failed_booking_does_not_flash_a_toast(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));
        $user = $this->staff();

        // خانة طلاب — ممنوعة على الموظف، فترجع بخطأ بلا أي إشعار نجاح
        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('error');
        $response->assertSessionMissing('toast');
    }
}
