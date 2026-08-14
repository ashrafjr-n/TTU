<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * نافذة الحجز محصورة بأيام دوام العيادة (الأحد–الخميس) وبنهاية أسبوع العيادة
 * الحالي (حدّ السبت نفسه المستخدم بـcurrentWeekDates ولوحة الدكتور)، بحد أقصى
 * 3 أيام.
 *
 * قبل هذا كانت "اليوم + يومين" تقويميًا: تعرض الجمعة/السبت (عطلة) كأيام قابلة
 * للحجز، وتمتد لأسبوع لاحق. كل يوم من أيام الأسبوع مغطى هنا صراحةً بعدد
 * الأيام وهويتها معًا، لا بعددها وحده.
 *
 * التواريخ المرجعية (أغسطس 2026): 15 سبت، 16 أحد، 17 اثنين، 18 ثلاثاء،
 * 19 أربعاء، 20 خميس، 21 جمعة.
 */
class BookingWindowWeekdayTest extends TestCase
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

    /** @return list<string> */
    private function windowOn(string $date): array
    {
        Carbon::setTestNow(Carbon::parse($date)->setTime(8, 0));

        return collect(Booking::bookableDates())->map->toDateString()->all();
    }

    // ------------------------------------------------------------------
    // كل يوم بالأسبوع: العدد والهوية معًا
    // ------------------------------------------------------------------

    public function test_sunday_offers_sunday_monday_and_tuesday(): void
    {
        $this->assertSame(
            ['2026-08-16', '2026-08-17', '2026-08-18'],
            $this->windowOn('2026-08-16')
        );
    }

    public function test_monday_offers_monday_tuesday_and_wednesday(): void
    {
        $this->assertSame(
            ['2026-08-17', '2026-08-18', '2026-08-19'],
            $this->windowOn('2026-08-17')
        );
    }

    public function test_tuesday_offers_tuesday_wednesday_and_thursday(): void
    {
        $this->assertSame(
            ['2026-08-18', '2026-08-19', '2026-08-20'],
            $this->windowOn('2026-08-18')
        );
    }

    public function test_wednesday_offers_only_wednesday_and_thursday(): void
    {
        // لا جمعة، ولا امتداد لأسبوع لاحق — يومان فقط
        $this->assertSame(
            ['2026-08-19', '2026-08-20'],
            $this->windowOn('2026-08-19')
        );
    }

    public function test_thursday_offers_only_thursday(): void
    {
        // آخر يوم عمل قبل إعادة ضبط الأسبوع يوم السبت — يوم واحد
        $this->assertSame(
            ['2026-08-20'],
            $this->windowOn('2026-08-20')
        );
    }

    public function test_friday_offers_nothing(): void
    {
        $this->assertSame([], $this->windowOn('2026-08-21'));
    }

    public function test_thursday_before_close_hour_still_offers_thursday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(15, 59));

        $this->assertSame(['2026-08-20'], collect(Booking::bookableDates())->map->toDateString()->all());
    }

    public function test_thursday_at_close_hour_closes_the_window(): void
    {
        // 4 عصرًا بالضبط (Booking::CLOSE_HOUR) — لحظة الإغلاق نفسها، لا بعدها
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(16, 0));

        $this->assertSame([], Booking::bookableDates());
    }

    public function test_saturday_reopens_with_next_sunday_and_monday(): void
    {
        // السبت حالة خاصة: يعاد فتح الحجز لأول يومي عمل بالأسبوع القادم
        // (الأحد والاثنين)، لا نافذة فارغة كما كان سابقًا
        $this->assertSame(
            ['2026-08-16', '2026-08-17'],
            $this->windowOn('2026-08-15')
        );
    }

    public function test_saturday_reopening_is_not_gated_by_time_of_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15')->setTime(23, 30));

        $this->assertSame(
            ['2026-08-16', '2026-08-17'],
            collect(Booking::bookableDates())->map->toDateString()->all()
        );
    }

    // ------------------------------------------------------------------
    // خصائص عامة تسري على كل الأيام
    // ------------------------------------------------------------------

    public function test_no_window_ever_contains_a_closed_day_or_crosses_the_week_boundary(): void
    {
        foreach (['2026-08-15', '2026-08-16', '2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21'] as $day) {
            Carbon::setTestNow(Carbon::parse($day)->setTime(8, 0));

            $dates = Booking::bookableDates();
            $lastWorkingDay = Carbon::parse($day)->startOfWeek(Carbon::SATURDAY)->addDays(5);

            $this->assertLessThanOrEqual(Booking::BOOKING_WINDOW_DAYS, count($dates), "نافذة $day تجاوزت الحد الأقصى");

            foreach ($dates as $date) {
                $this->assertContains($date->dayOfWeek, [0, 1, 2, 3, 4], "نافذة $day تضم يومًا مغلقًا: {$date->toDateString()}");
                $this->assertTrue($date->gte(Carbon::parse($day)), "نافذة $day تضم يومًا ماضيًا");
                $this->assertTrue($date->lte($lastWorkingDay), "نافذة $day تخطّت نهاية الأسبوع");
            }
        }
    }

    public function test_the_window_agrees_with_the_saturday_week_convention_used_by_the_doctor_dashboard(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19')->setTime(8, 0)); // أربعاء

        $weekDates = collect(Booking::currentWeekDates())->map->toDateString()->all();

        // كل يوم بنافذة الحجز يقع ضمن نفس "الأسبوع" الذي تراه لوحة الدكتور
        foreach (Booking::bookableDates() as $date) {
            $this->assertContains($date->toDateString(), $weekDates);
        }
    }

    // ------------------------------------------------------------------
    // انعكاس ذلك على الصفحة و store()
    // ------------------------------------------------------------------

    public function test_the_booking_page_renders_one_day_tab_on_thursday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(8, 0));

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('id="day-tab-0"', false);
        $response->assertDontSee('id="day-tab-1"', false);
        $response->assertDontSee('id="day-tab-2"', false);
    }

    public function test_the_booking_page_renders_two_day_tabs_on_wednesday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19')->setTime(8, 0));

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('id="day-tab-0"', false);
        $response->assertSee('id="day-tab-1"', false);
        $response->assertDontSee('id="day-tab-2"', false);
    }

    public function test_the_booking_page_renders_three_day_tabs_on_sunday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-16')->setTime(8, 0));

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('id="day-tab-0"', false);
        $response->assertSee('id="day-tab-1"', false);
        $response->assertSee('id="day-tab-2"', false);
    }

    public function test_the_booking_page_shows_the_closed_state_on_friday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21')->setTime(8, 0));

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('العيادة مغلقة اليوم');
        $response->assertSee('id="clinicClosedModalOverlay"', false);
        // ليست حالة "الحد الفصلي" — every() على نافذة فارغة تعود true، فلولا
        // ترتيب الفحوص لظهرت رسالة خاطئة تمامًا هنا
        $response->assertDontSee('بلغت الحد الأقصى لحجوزات هذا الفصل');
        $response->assertDontSee('id="bookModalOverlay"', false);
        $response->assertDontSee('id="day-tab-0"', false);
    }

    public function test_the_booking_page_reopens_with_two_day_tabs_on_saturday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15')->setTime(8, 0));

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        $response->assertOk();
        $response->assertDontSee('العيادة مغلقة اليوم');
        $response->assertSee('id="day-tab-0"', false);
        $response->assertSee('id="day-tab-1"', false);
        $response->assertDontSee('id="day-tab-2"', false);
    }

    public function test_the_booking_page_labels_saturdays_two_tabs_as_tomorrow_and_day_after(): void
    {
        // الأحد (تبويب 0) هو غدًا فعليًا بالنسبة للسبت، لا "اليوم" — وهذا بالضبط
        // ما كسره الاعتماد القديم على ترتيب المصفوفة (index) بدل علاقة التاريخ
        // الحقيقية بـ"اليوم" (راجع Booking::dayLabel)
        Carbon::setTestNow(Carbon::parse('2026-08-15')->setTime(8, 0));

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        // لا نفحص غياب booking.day.today ("اليوم") عبر assertDontSee: النص
        // يظهر بصرف النظر عن هذا التبويب ضمن نصوص أخرى بالصفحة (مثل aria-label
        // "اختر اليوم") فليس علامة موثوقة على غياب تبويب "اليوم" تحديدًا
        $response->assertOk();
        $response->assertSee(__('booking.day.tomorrow'));
        $response->assertSee(__('booking.day.day_after'));
    }

    public function test_thursday_after_close_hour_shows_the_closed_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(16, 0));

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('العيادة مغلقة اليوم');
        $response->assertDontSee('id="day-tab-0"', false);
    }

    public function test_the_closed_state_is_localized_in_english(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21')->setTime(8, 0));

        $response = $this->actingAs($this->student())
            ->withSession(['locale' => 'en'])
            ->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('The clinic is closed today');
        $response->assertDontSee('العيادة مغلقة اليوم');
    }

    public function test_closed_window_description_is_built_from_the_same_constants_as_the_closed_check(): void
    {
        $this->assertSame(
            'الحجز مغلق من يوم الخميس الساعة 4:00 مساءً حتى نهاية يوم الجمعة، ويعاد فتحه من يوم السبت حتى يوم الخميس الساعة 4:00 مساءً.',
            Booking::closedWindowDescription()
        );
    }

    public function test_the_closed_state_message_reflects_the_real_thursday_to_friday_window_not_saturday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(16, 0)); // خميس، بعد الإغلاق

        $response = $this->actingAs($this->student())->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee(Booking::closedWindowDescription());
        // النص القديم الخاطئ (كان يذكر السبت كيوم عطلة ويفتح الأحد فقط)
        $response->assertDontSee('يفتح الحجز من جديد يوم الأحد');
    }

    public function test_the_closed_state_message_is_localized_in_english(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21')->setTime(8, 0)); // جمعة

        $response = $this->actingAs($this->student())
            ->withSession(['locale' => 'en'])
            ->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('Booking is closed from Thursday at 4:00 PM until the end of Friday, and reopens from Saturday until Thursday at 4:00 PM.');
        $response->assertDontSee('Booking reopens on Sunday');
    }

    public function test_chatbot_static_content_and_system_prompt_do_not_claim_saturday_is_closed(): void
    {
        // نفس الخلل الذي أُصلح بمودال صفحة الحجز كان موجودًا أيضًا بمحتوى
        // الشات بوت الثابت (سؤال "كيف أحجز موعدًا؟") وبتعليمات نموذج الذكاء
        // الاصطناعي نفسها — كلاهما بقي يذكر "الجمعة والسبت عطلة... يفتح الحجز
        // من جديد يوم الأحد" بعد أن صار السبت يعيد فتح الحجز فعليًا
        foreach (['ar', 'en'] as $locale) {
            $chatbot = require base_path("lang/{$locale}/chatbot.php");
            $flat = json_encode($chatbot, JSON_UNESCAPED_UNICODE);

            $this->assertStringNotContainsString('يفتح الحجز من جديد يوم الأحد', $flat);
            $this->assertStringNotContainsString('reopening on Sunday', $flat);
        }

        $method = new \ReflectionMethod(\App\Services\ChatbotService::class, 'systemPrompt');
        $method->setAccessible(true);
        $prompt = $method->invoke(new \App\Services\ChatbotService());

        $this->assertStringNotContainsString('booking reopens on Sunday', $prompt);
        $this->assertStringContainsString('Saturday is a special case', $prompt);
    }

    public function test_store_rejects_a_booking_on_a_closed_day_even_without_an_explicit_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21')->setTime(8, 0)); // جمعة
        $user = $this->student();

        // بلا 'date' — يفترض المتحكّم "اليوم"، وهو يوم عطلة لا تغطيه Rule::in
        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('error');
        $this->assertSame(__('booking.errors.clinic_closed'), session('error'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_store_rejects_booking_today_on_saturday_since_saturday_itself_has_no_slots(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15')->setTime(8, 0)); // سبت
        $user = $this->student();

        // بلا 'date' — يفترض المتحكّم "اليوم" (السبت نفسه)، وهو ليس ضمن
        // bookableDates() رغم أن النافذة معاد فتحها (الأحد/الاثنين فقط)
        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('error');
        $this->assertSame(__('booking.errors.clinic_closed'), session('error'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_store_accepts_an_explicit_sunday_date_booked_from_saturday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15')->setTime(8, 0)); // سبت
        $user = $this->student();

        $response = $this->actingAs($user)->post(route('booking.store'), [
            'date' => '2026-08-16', 'hour' => 9, 'minute' => 0,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'booking_date' => '2026-08-16 00:00:00',
            'status' => 'confirmed',
        ]);
    }

    public function test_store_rejects_an_explicit_friday_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(8, 0)); // خميس
        $user = $this->student();

        $response = $this->actingAs($user)->post(route('booking.store'), [
            'date' => '2026-08-21', 'hour' => 9, 'minute' => 0,
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_store_rejects_a_date_that_belongs_to_next_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19')->setTime(8, 0)); // أربعاء
        $user = $this->student();

        // الأحد القادم (23 أغسطس) — كان ضمن النافذة القديمة تقويميًا لو كانت
        // 3 أيام مطلقة، وهو الآن خارجها لأنها لا تعبر حدّ الأسبوع
        $response = $this->actingAs($user)->post(route('booking.store'), [
            'date' => '2026-08-23', 'hour' => 9, 'minute' => 0,
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_thursday_still_allows_booking_the_single_available_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20')->setTime(8, 0));
        $user = $this->student();

        $response = $this->actingAs($user)->post(route('booking.store'), ['hour' => 9, 'minute' => 0]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'booking_date' => '2026-08-20 00:00:00',
            'status' => 'confirmed',
        ]);
    }
}
