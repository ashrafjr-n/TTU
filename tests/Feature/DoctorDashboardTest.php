<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => 'doctor-audit']);
    }

    public function test_dashboard_renders_for_today_by_default(): void
    {
        $response = $this->actingAs($this->doctor())->get(route('dashboard.doctor'));

        $response->assertOk();
    }

    public function test_dashboard_accepts_a_valid_date_query(): void
    {
        $response = $this->actingAs($this->doctor())
            ->get(route('dashboard.doctor', ['date' => '2026-08-10']));

        $response->assertOk();
    }

    public function test_an_invalid_date_query_is_rejected_instead_of_crashing(): void
    {
        $response = $this->actingAs($this->doctor())
            ->get(route('dashboard.doctor', ['date' => 'not-a-date']));

        $response->assertSessionHasErrors('date');
    }
}
