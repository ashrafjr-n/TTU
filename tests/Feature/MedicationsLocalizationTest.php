<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationsLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_medications_page_switches_language(): void
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        $ar = $this->actingAs($student)->get(route('medications.mine'));
        $ar->assertOk();
        $ar->assertSee('لا توجد تقارير بعد');

        $en = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('medications.mine'));
        $en->assertOk();
        $en->assertSee('No reports yet');
        $en->assertSee('Book your appointment now');
        $en->assertDontSee('لا توجد تقارير بعد');
    }
}
