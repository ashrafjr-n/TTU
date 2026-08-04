<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorDashboardLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_dashboard_switches_language(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('########')]);

        $ar = $this->actingAs($doctor)->get(route('dashboard.doctor'));
        $ar->assertOk();
        $ar->assertSee('لوحة الطبيب');
        $ar->assertSee('جدول الحجوزات');

        $en = $this->actingAs($doctor)->withSession(['locale' => 'en'])->get(route('dashboard.doctor'));
        $en->assertOk();
        $en->assertSee('Doctor Dashboard');
        $en->assertSee('Bookings schedule');
        $en->assertDontSee('لوحة الطبيب');

        // labels used by the visit-report modal's Alpine component are
        // embedded server-side too, so they must flip with the locale
        $en->assertSee('Attach Report');
        $en->assertDontSee('إرفاق تقرير');
    }
}
