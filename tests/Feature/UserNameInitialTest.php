<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * أسماء الأطباء مخزَّنة بلقب بادئ ("د. أشرف جرابعة") — nameInitial() يجب أن
 * يتجاهل اللقب ويرجع أول حرف من الاسم الفعلي، لا حرف اللقب نفسه.
 */
class UserNameInitialTest extends TestCase
{
    public function test_a_doctor_name_initial_skips_the_title_prefix(): void
    {
        $doctor = new User(['name' => 'د. أشرف جرابعة']);

        $this->assertSame('أ', $doctor->nameInitial());
    }

    public function test_a_doctor_name_initial_handles_a_period_with_no_trailing_space(): void
    {
        $doctor = new User(['name' => 'د.أشرف جرابعة']);

        $this->assertSame('أ', $doctor->nameInitial());
    }

    public function test_a_non_doctor_name_initial_is_unaffected(): void
    {
        $student = new User(['name' => 'أحمد خالد']);

        $this->assertSame('أ', $student->nameInitial());
    }

    public function test_a_name_that_happens_to_start_with_the_letter_daal_is_not_mistaken_for_a_title(): void
    {
        // اسم عادي يبدأ بحرف الدال فعليًا (لا لقب) — لازم يبقى "د" هو الحرف الصحيح
        $user = new User(['name' => 'داود سالم']);

        $this->assertSame('د', $user->nameInitial());
    }
}
