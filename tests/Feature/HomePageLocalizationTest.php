<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_fully_arabic_by_default(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('رعايتك الصحية بخطوة واحدة.');
        $response->assertSee('اختر نوع الحساب للمتابعة');
        $response->assertSee('كيف تحجز موعدك؟');
        $response->assertSee('عن عيادة TTU');
    }

    public function test_home_page_fully_switches_to_english(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('home'));

        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('Your health care, in one step.');
        $response->assertSee('Choose your account type to continue');
        $response->assertSee('How do you book your appointment?');
        $response->assertSee('About TTU Clinic');

        $response->assertDontSee('رعايتك الصحية');
        $response->assertDontSee('اختر نوع الحساب للمتابعة');
    }
}
