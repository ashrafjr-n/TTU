<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_switches_language(): void
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        $ar = $this->actingAs($student)->get(route('dashboard.student'));
        $ar->assertOk();
        $ar->assertSee('لوحة الطالب');
        $ar->assertSee('ماذا تريد أن تفعل اليوم؟');

        $en = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('dashboard.student'));
        $en->assertOk();
        $en->assertSee('Student Dashboard');
        $en->assertSee('What would you like to do today?');
        $en->assertDontSee('لوحة الطالب');
    }

    public function test_staff_dashboard_switches_language(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'identifier' => fake()->unique()->numerify('########')]);

        $en = $this->actingAs($staff)->withSession(['locale' => 'en'])->get(route('dashboard.staff'));
        $en->assertOk();
        $en->assertSee('Staff Dashboard');
        $en->assertSee('Staff ID');
    }
}
