<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DoctorAttendance;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * التحقق من أن التصحيح على APP_TIMEZONE (كانت UTC ثابتة بدل قراءة .env) لم
 * يكسر أي من المهام المرتبطة بساعة العيادة المحلية: الجدولة اليومية لانصراف
 * الدكاترة الساعة 4 عصرًا، وتذكير المواعيد القادمة خلال ساعة تقريبًا.
 */
class TimezoneBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_app_timezone_reflects_amman_not_utc(): void
    {
        $this->assertSame('Asia/Amman', config('app.timezone'));
        $this->assertSame('Asia/Amman', now()->getTimezone()->getName());
    }

    /**
     * هذا الاختبار يغطي الثغرة التي لم يغطها test_app_timezone_reflects_amman_not_utc
     * أعلاه: ذاك يتحقق من config('app.timezone') كما يُحمَّل هنا محليًا، حيث
     * .env يضبط APP_TIMEZONE=Asia/Amman فعليًا — فيمر بنجاح سواء كان fallback
     * بـ config/app.php صحيحًا أو لا، ولا يكشف شيئًا.
     *
     * البيئة الحقيقية المُعطّلة (Render) لا تملك .env على الإطلاق — هو مستبعد
     * صراحة من كل من .gitignore و.dockerignore، فلا ينتقل للحاوية أبدًا. القيمة
     * الوحيدة التي تصل APP_TIMEZONE هناك هي متغيرات بيئة المضيف نفسه؛ لو لم
     * يضبطها أحد يدويًا هناك، يسقط env('APP_TIMEZONE', ...) على القيمة
     * الافتراضية المكتوبة حرفيًا داخل config/app.php. فحص ذلك محليًا يستوجب
     * محاكاة غياب المتغير فعليًا (لا الاعتماد على .env المحلي)، فنُفرغه من كل
     * مصادر env() الثلاثة (putenv/$_ENV/$_SERVER) ثم نعيد تحميل config/app.php
     * كملف مستقل تمامًا كما يفعل Laravel عند الإقلاع.
     */
    public function test_timezone_config_falls_back_to_amman_when_app_timezone_unset_in_host_env(): void
    {
        $original = [
            'putenv' => getenv('APP_TIMEZONE'),
            'env' => $_ENV['APP_TIMEZONE'] ?? null,
            'server' => $_SERVER['APP_TIMEZONE'] ?? null,
        ];

        putenv('APP_TIMEZONE');
        unset($_ENV['APP_TIMEZONE'], $_SERVER['APP_TIMEZONE']);

        try {
            $resolved = (require config_path('app.php'))['timezone'];
        } finally {
            if ($original['putenv'] !== false) {
                putenv("APP_TIMEZONE={$original['putenv']}");
            }
            if ($original['env'] !== null) {
                $_ENV['APP_TIMEZONE'] = $original['env'];
            }
            if ($original['server'] !== null) {
                $_SERVER['APP_TIMEZONE'] = $original['server'];
            }
        }

        // لو كانت هذه القيمة 'UTC' (القيمة الافتراضية القديمة لـ Laravel)،
        // فهذا بالضبط ما جعل توقيت حضور/انصراف الدكاترة يظهر مُبكِّرًا بـ 3
        // ساعات على أي بيئة نسي أحد ضبط APP_TIMEZONE عليها صراحةً.
        $this->assertSame('Asia/Amman', $resolved);
    }

    public function test_auto_checkout_command_runs_at_local_4pm_not_utc_4pm(): void
    {
        // الساعة 4:05 عصرًا بتوقيت عمّان المحلي — لو كان الإعداد ما زال UTC
        // (أي أن التطبيق يظن هذا التوقيت هو 4:05 UTC = 7:05 مساءً بعمّان)،
        // هذا الاختبار كان سيبقى يمر خطأً لأن whereDate('date', today())
        // ستستخدم "اليوم" بتوقيت مختلف عن نية "4 عصرًا محليًا" — لذلك نتحقق
        // أيضًا أن ساعة الجدولة نفسها (schedule:list) تُبنى على نفس المنطقة
        // الزمنية في اختبار منفصل (test_scheduled_auto_checkout_uses_local_timezone).
        Carbon::setTestNow(Carbon::today()->setTime(16, 5));

        $doctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('########')]);

        $attendance = DoctorAttendance::create([
            'doctor_id' => $doctor->id,
            'date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(9, 0),
            'check_out_at' => null,
            'is_auto_checkout' => false,
        ]);

        $this->artisan('attendance:auto-checkout')->assertExitCode(0);

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_at);
        $this->assertTrue($attendance->is_auto_checkout);
        // وقت الانصراف المسجَّل يجب أن يطابق "الآن" بتوقيت عمّان المحلي (16:05)
        $this->assertSame('16:05:00', $attendance->check_out_at->format('H:i:s'));
    }

    public function test_auto_checkout_does_not_touch_doctors_who_already_checked_out(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(16, 5));

        $doctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('########')]);
        $checkOutTime = Carbon::today()->setTime(14, 30);

        $attendance = DoctorAttendance::create([
            'doctor_id' => $doctor->id,
            'date' => Carbon::today(),
            'check_in_at' => Carbon::today()->setTime(9, 0),
            'check_out_at' => $checkOutTime,
            'is_auto_checkout' => false,
        ]);

        $this->artisan('attendance:auto-checkout')->assertExitCode(0);

        $attendance->refresh();
        $this->assertFalse($attendance->is_auto_checkout);
        $this->assertSame('14:30:00', $attendance->check_out_at->format('H:i:s'));
    }

    public function test_scheduled_auto_checkout_uses_local_timezone(): void
    {
        // withSchedule() في bootstrap/app.php يسجّل أحداث الجدولة فقط عند
        // Artisan::starting — لازم نُشغّل أمر آرتيزان (أي أمر) أولًا كي يُبنى
        // الجدول قبل قراءته، وإلا يرجع events() فارغة.
        $this->artisan('inspire')->assertExitCode(0);

        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

        $event = collect($schedule->events())
            ->first(fn ($e) => str_contains($e->command, 'attendance:auto-checkout'));

        $this->assertNotNull($event);
        // بدون schedule_timezone صريح، Laravel يعتمد على app.timezone — تحقق
        // أن هذا فعلًا Asia/Amman الآن (لا UTC كما كان قبل التصحيح، وإلا لكانت
        // المهمة تشتغل الساعة 7 مساءً بتوقيت عمّان بدل 4 عصرًا)
        $this->assertSame('Asia/Amman', (string) $event->timezone);

        // الساعة 3:59 مساءً محليًا — لسه ما حان وقت التشغيل
        Carbon::setTestNow(Carbon::today()->setTime(15, 59));
        $this->assertFalse($event->isDue($this->app));

        // الساعة 4:00 عصرًا محليًا بالضبط — هذه لحظة التشغيل المقصودة
        Carbon::setTestNow(Carbon::today()->setTime(16, 0));
        $this->assertTrue($event->isDue($this->app));
    }

    public function test_reminder_job_notifies_bookings_within_the_hour_using_local_clock(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        // موعد الساعة 9:00 — أي بعد ساعة تقريبًا من "الآن" المحلي (8:00)
        $booking = Booking::create([
            'user_id' => $student->id,
            'booking_date' => Carbon::today(),
            'booking_hour' => 9,
            'booking_minute' => 0,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);

        // موعد بعيد جدًا (14:00) يجب ألا يُذكَّر به الآن
        $farBooking = Booking::create([
            'user_id' => $student->id,
            'booking_date' => Carbon::today(),
            'booking_hour' => 14,
            'booking_minute' => 0,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);

        $this->artisan('notifications:send-reminders')->assertExitCode(0);

        Notification::assertSentTo($student, AppointmentReminder::class, function ($notification) use ($booking) {
            $data = $notification->toDatabase($booking->user);

            return $data['body_key'] === 'notifications.reminder.body'
                && $data['body_params']['time'] === $booking->timeLabel();
        });
        Notification::assertSentToTimes($student, AppointmentReminder::class, 1);

        $booking->refresh();
        $farBooking->refresh();
        $this->assertNotNull($booking->reminder_1h_sent_at);
        $this->assertNull($farBooking->reminder_1h_sent_at);
    }
}
