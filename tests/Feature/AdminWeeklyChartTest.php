<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * مخطط "حجوزات الأسبوع" بلوحة المدير كان يحسب "الأسبوع الحالي" بعُرف
 * مستقل (أحد→سبت) يختلف عن العُرف المستخدم بكل مكان آخر بالتطبيق
 * (Booking::weekRange وbookableDates وcurrentWeekDates، كلها سبت→جمعة).
 * هذه الاختبارات تتحقق أن المخطط الآن يستخدم نفس عُرف السبت، وأن التسمية
 * المعروضة تطابق المدى الفعلي المحسوب.
 *
 * التاريخ المرجعي 19 أغسطس 2026 (أربعاء) يقع ضمن أسبوع 15–21 أغسطس بعُرف
 * السبت (weekRange(0))، بينما العُرف القديم (أحد→سبت) كان يحسب 16–22 أغسطس
 * لنفس اليوم — 15 و22 أغسطس هما بالضبط نقطتا الاختلاف بين العُرفين.
 */
class AdminWeeklyChartTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function bookingOn(string $date, int $hour = 9, int $minute = 0): Booking
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        return Booking::create([
            'user_id' => $student->id,
            'booking_date' => $date,
            'booking_hour' => $hour,
            'booking_minute' => $minute,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    public function test_chart_counts_saturday_as_the_first_day_of_the_current_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19')->setTime(10, 0)); // أربعاء

        // 15 أغسطس (سبت): أول يوم بالأسبوع بعُرف السبت — يجب أن يُحتسب
        $this->bookingOn('2026-08-15');

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('weekBookingsTotal', 1);
    }

    public function test_chart_excludes_next_saturday_which_the_old_sunday_start_convention_wrongly_included(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19')->setTime(10, 0)); // أربعاء

        // 22 أغسطس (سبت الأسبوع القادم): خارج أسبوع 15-21 بعُرف السبت، رغم
        // أن العُرف القديم (أحد→سبت) كان يضمه ضمن "هذا الأسبوع" (16-22)
        $this->bookingOn('2026-08-22');

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('weekBookingsTotal', 0);
    }

    public function test_chart_places_the_daily_count_on_the_correct_day_not_always_zero(): void
    {
        // يغطي خللًا منفصلًا عن ترتيب الأسبوع: booking_date (date cast) يُخزَّن
        // فعليًا بصيغة "Y-m-d H:i:s"، فاستعلام groupBy/pluck خام لا يمر بتحويل
        // Eloquent كان يُعيد مفاتيح لا تطابق toDateString() المستخدمة بالبحث
        // لاحقًا — فتظهر كل أعمدة المخطط صفرًا مهما وُجدت حجوزات فعلية
        Carbon::setTestNow(Carbon::parse('2026-08-19')->setTime(10, 0));

        $this->bookingOn('2026-08-17', 9, 0); // اثنين
        $this->bookingOn('2026-08-17', 10, 0);
        $this->bookingOn('2026-08-18', 9, 0); // ثلاثاء

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $weekChart = $response->viewData('weekChart');

        // labels/data متوازيان بترتيب الأيام من weekStart؛ نبني خريطة تاريخ→فهرس
        // من نفس منطق weekRange(0) (سبت 15 → جمعة 21) لتحديد فهرس كل يوم
        [$weekStart] = Booking::weekRange(0);
        $dates = [];
        for ($d = $weekStart->copy(), $i = 0; $i < 7; $d->addDay(), $i++) {
            $dates[$d->toDateString()] = $i;
        }

        $this->assertSame(2, $weekChart['data'][$dates['2026-08-17']]);
        $this->assertSame(1, $weekChart['data'][$dates['2026-08-18']]);
        $this->assertSame(0, $weekChart['data'][$dates['2026-08-15']]);
    }

    public function test_chart_agrees_with_booking_week_range_helper(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19')->setTime(10, 0));

        [$start, $end] = Booking::weekRange(0);
        $this->assertSame('2026-08-15', $start->toDateString());
        $this->assertSame('2026-08-21', $end->toDateString());
    }

    public function test_subheading_label_reads_saturday_to_friday_not_sunday_to_saturday(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('admin_dashboard.week_chart.subheading'));
        $response->assertDontSee('الأحد–السبت');
        $response->assertDontSee('Sunday–Saturday');
    }
}
