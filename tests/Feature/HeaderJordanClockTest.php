<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ساعة "توقيت الأردن" بالهيدر الداخلي (غير الشفاف) كانت بلون باهت
 * (text-ttu-gray للتسمية، وdark:text-ttu-white للرقم — التي تُحل فعليًا للون
 * داكن تحت .dark بسبب طريقة تبديل متغيّرات الألوان بهذا التطبيق). كلاهما
 * الآن text-ttu-black فقط — يتبدّل تلقائيًا بين أسود بالوضع العادي وأبيض
 * بالوضع الليلي دون حاجة لـdark: صريحة (راجع app.css).
 */
class HeaderJordanClockTest extends TestCase
{
    use RefreshDatabase;

    public function test_jordan_clock_label_and_time_use_the_readable_black_text_class(): void
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        $response = $this->actingAs($student)->get(route('dashboard.student'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="jordan-clock"', $html);
        $this->assertStringContainsString(
            'class="text-[10px] font-bold text-ttu-black leading-none whitespace-nowrap">'.__('common.header.jordan_time'),
            $html
        );
        $this->assertStringContainsString(
            'id="jordan-clock-time" class="text-xs font-bold text-ttu-black tabular-nums leading-none"',
            $html
        );

        // الأنماط الباهتة القديمة يجب ألا تظهر على شارة الساعة بعد الآن
        $this->assertStringNotContainsString('text-ttu-gray leading-none whitespace-nowrap', $html);
        $this->assertStringNotContainsString('id="jordan-clock-time" class="text-xs font-bold text-ttu-black dark:text-ttu-white', $html);
    }
}
