<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutContactLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_switches_language(): void
    {
        $ar = $this->get(route('about'));
        $ar->assertOk();
        $ar->assertSee('عن عيادة TTU');
        $ar->assertSee('هل أنت مستعد لحجز موعدك؟');

        $en = $this->withSession(['locale' => 'en'])->get(route('about'));
        $en->assertOk();
        $en->assertSee('About TTU Clinic');
        $en->assertSee('Ready to book your appointment?');
        $en->assertDontSee('عن عيادة TTU');
    }

    public function test_contact_page_switches_language(): void
    {
        $student = User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
        $doctor = User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('########')]);

        $ar = $this->actingAs($student)->get(route('contact'));
        $ar->assertOk();
        $ar->assertSee('نسعد بتواصلك معنا');
        $ar->assertSee($doctor->name);

        $en = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('contact'));
        $en->assertOk();
        $en->assertSee("We'd love to hear from you");
        $en->assertDontSee('نسعد بتواصلك معنا');
    }
}
