<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * notifications:send-reminders يرسل تذكيرين مستقلين لكل حجز: قبل ساعة
 * تقريبًا (reminder_1h_sent_at) وقبل 15 دقيقة تقريبًا (reminder_15m_sent_at)
 * — كل تذكير له عمود "تم الإرسال" خاص به، فلا يمنع أحدهما الآخر.
 */
class AppointmentReminderWindowsTest extends TestCase
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

    private function bookingAt(User $user, int $hour, int $minute = 0): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'booking_date' => Carbon::today(),
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    public function test_a_booking_15_minutes_away_gets_the_15_minute_reminder_only(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::today()->setTime(8, 45));

        $student = $this->student();
        // الموعد الساعة 9:00 — بعد 15 دقيقة بالضبط من "الآن"
        $booking = $this->bookingAt($student, 9, 0);

        $this->artisan('notifications:send-reminders')->assertExitCode(0);

        Notification::assertSentTo($student, AppointmentReminder::class, function ($notification) {
            $data = $notification->toDatabase($notification);

            return $data['body_key'] === 'notifications.reminder.body_15m';
        });
        Notification::assertSentToTimes($student, AppointmentReminder::class, 1);

        $booking->refresh();
        $this->assertNotNull($booking->reminder_15m_sent_at);
        $this->assertNull($booking->reminder_1h_sent_at);
    }

    public function test_a_booking_can_receive_both_the_1_hour_and_15_minute_reminder_over_time(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $student = $this->student();
        // الموعد الساعة 9:00 — بعد ساعة بالضبط من "الآن"
        $booking = $this->bookingAt($student, 9, 0);

        $this->artisan('notifications:send-reminders')->assertExitCode(0);

        $booking->refresh();
        $this->assertNotNull($booking->reminder_1h_sent_at);
        $this->assertNull($booking->reminder_15m_sent_at);

        // الوقت يتقدم لـ 8:45 — الموعد صار بعد 15 دقيقة
        Carbon::setTestNow(Carbon::today()->setTime(8, 45));

        $this->artisan('notifications:send-reminders')->assertExitCode(0);

        $booking->refresh();
        $this->assertNotNull($booking->reminder_1h_sent_at);
        $this->assertNotNull($booking->reminder_15m_sent_at);

        Notification::assertSentToTimes($student, AppointmentReminder::class, 2);
    }

    public function test_running_the_command_twice_does_not_double_send_the_same_reminder(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::today()->setTime(8, 45));

        $student = $this->student();
        $this->bookingAt($student, 9, 0);

        $this->artisan('notifications:send-reminders')->assertExitCode(0);
        $this->artisan('notifications:send-reminders')->assertExitCode(0);

        Notification::assertSentToTimes($student, AppointmentReminder::class, 1);
    }

    public function test_doctors_never_receive_appointment_reminders(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::today()->setTime(8, 45));

        // لا يوجد فعليًا مسار ينشئ حجزًا بمستخدم دكتور (بوابة /booking
        // مقصورة على role:student,staff) — هذا الاختبار يتحقق من أن أمر
        // التذكير نفسه يحمي من هذه الحالة صراحة (شرط أمان إضافي) حتى لو
        // وُجد صف حجز شاذ بمستخدم دكتور لأي سبب.
        $doctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
        $booking = $this->bookingAt($doctor, 9, 0);

        $this->artisan('notifications:send-reminders')->assertExitCode(0);

        Notification::assertNothingSentTo($doctor);

        $booking->refresh();
        $this->assertNull($booking->reminder_15m_sent_at);
    }

    public function test_a_booking_outside_both_windows_receives_no_reminder(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $student = $this->student();
        // الموعد الساعة 14:00 — بعيد جدًا عن نافذتي الساعة و15 الدقيقة
        $booking = $this->bookingAt($student, 14, 0);

        $this->artisan('notifications:send-reminders')->assertExitCode(0);

        Notification::assertNothingSentTo($student);

        $booking->refresh();
        $this->assertNull($booking->reminder_1h_sent_at);
        $this->assertNull($booking->reminder_15m_sent_at);
    }
}
