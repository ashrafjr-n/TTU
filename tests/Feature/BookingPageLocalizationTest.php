<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingPageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_booking_page_switches_language(): void
    {
        // شبكة الأوقات تظهر بأيام الدوام فقط، فيُثبَّت "اليوم" على أحد
        // (16 أغسطس 2026) وإلا عرضت الصفحة مودال "العيادة مغلقة" كلما صادف
        // تشغيل الاختبار جمعة أو سبتًا
        Carbon::setTestNow(Carbon::create(2026, 8, 16, 8, 0));

        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        $ar = $this->actingAs($student)->get(route('booking.index'));
        $ar->assertOk();
        $ar->assertSee('احجز موعدك');
        $ar->assertSee('اليوم');

        $en = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('booking.index'));
        $en->assertOk();
        $en->assertSee('Book your appointment');
        $en->assertSee('Today');
        $en->assertDontSee('احجز موعدك');
    }

    public function test_active_booking_modal_switches_language(): void
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
        Booking::create([
            'user_id' => $student->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_hour' => 10,
            'booking_minute' => 0,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);

        $en = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('booking.index'));
        $en->assertOk();
        $en->assertSee('You have an active booking');
        $en->assertSee('Cancel this booking');
    }
}
