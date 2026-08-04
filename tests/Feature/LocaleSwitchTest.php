<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تبديل اللغة: يحفظ الاختيار بالجلسة، يبقى ساريًا عبر الصفحات اللاحقة
 * (SetLocale middleware)، ويغيّر اتجاه الصفحة (rtl/ltr) مع اللغة نفسها.
 */
class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_arabic_with_rtl_direction(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('lang="ar"', false);
        $response->assertSee('dir="rtl"', false);
    }

    public function test_switching_to_english_stores_it_in_session_and_redirects_back(): void
    {
        $response = $this->from(route('home'))->get(route('locale.switch', 'en'));

        $response->assertRedirect(route('home'));
        $this->assertSame('en', session('locale'));
    }

    public function test_the_english_locale_persists_across_a_later_request_and_flips_direction(): void
    {
        $this->withSession(['locale' => 'en'])->get(route('home'));

        $response = $this->withSession(['locale' => 'en'])->get(route('home'));

        $response->assertOk();
        $response->assertSee('lang="en"', false);
        $response->assertSee('dir="ltr"', false);
    }

    public function test_an_unsupported_locale_is_rejected(): void
    {
        $response = $this->get(route('locale.switch', 'fr'));

        $response->assertNotFound();
    }
}
