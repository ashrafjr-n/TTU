<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الإشعارات تُخزَّن كمفتاح ترجمة + معطيات (title_key/body_key + params) لا
 * كنص جاهز، فتظهر باللغة الحالية للمستخدم وقت العرض وليس وقت الإنشاء.
 */
class NotificationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_same_notification_renders_in_arabic_or_english_depending_on_session_locale(): void
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => 'notif-locale-1']);
        $booking = Booking::create([
            'user_id' => $student->id,
            'booking_date' => now()->toDateString(),
            'booking_hour' => 10,
            'booking_minute' => 0,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);

        $student->notify(new AppointmentReminder($booking));

        $arabic = $this->actingAs($student)->withSession(['locale' => 'ar'])->get(route('dashboard.student'));
        $arabic->assertOk();
        $arabic->assertSee('تذكير بموعدك');

        $english = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('dashboard.student'));
        $english->assertOk();
        $english->assertSee('Appointment reminder');
        $english->assertDontSee('تذكير بموعدك');
    }
}
