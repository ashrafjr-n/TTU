<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * وسوم <head> (العنوان/الوصف/Open Graph/الفافيكون) وشاشة الترحيب — كلاهما
 * معرّف بـ layouts/main، فالاختبار هنا يحرس سلوكهما عبر أنواع الصفحات.
 */
class LayoutHeadAndSplashTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    // ------------------------------------------------------------------
    // شاشة الترحيب — الصفحة الرئيسية وحدها
    // ------------------------------------------------------------------

    public function test_splash_screen_shows_on_the_home_page(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="splashScreen"', false);
    }

    public function test_splash_screen_still_shows_on_every_home_page_reload(): void
    {
        foreach (range(1, 3) as $ignored) {
            $this->get(route('home'))->assertSee('id="splashScreen"', false);
        }
    }

    public function test_splash_screen_does_not_show_on_other_public_pages(): void
    {
        foreach ([route('about'), route('login'), route('contact')] as $url) {
            $this->get($url)->assertDontSee('id="splashScreen"', false);
        }
    }

    public function test_splash_screen_does_not_show_on_authenticated_pages(): void
    {
        $response = $this->actingAs($this->student())->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertDontSee('id="splashScreen"', false);
    }

    /** اللوجوان يظهران مباشرة — بلا بطاقة/خلفية خلفهما */
    public function test_splash_logos_are_not_wrapped_in_a_card(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('images/TTU-logo.png', false);
        $response->assertSee('images/TTU-Clinic.png', false);
        $response->assertDontSee('logo-badge', false);
    }

    // ------------------------------------------------------------------
    // الفافيكون + Open Graph
    // ------------------------------------------------------------------

    public function test_favicon_and_open_graph_tags_are_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('rel="icon"', false);
        $response->assertSee('favicon/favicon-32x32.png', false);
        $response->assertSee('rel="apple-touch-icon"', false);
        $response->assertSee('property="og:type"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="viewport"', false);
    }

    /** ملفات الفافيكون نفسها موجودة فعلًا تحت public/ */
    public function test_favicon_files_exist_on_disk(): void
    {
        foreach ([
            'favicon.ico',
            'favicon/favicon-16x16.png',
            'favicon/favicon-32x32.png',
            'favicon/apple-touch-icon.png',
        ] as $path) {
            $this->assertFileExists(public_path($path), "ملف الفافيكون $path مفقود");
        }
    }

    // ------------------------------------------------------------------
    // العنوان/الوصف يتبعان لغة الموقع
    // ------------------------------------------------------------------

    public function test_title_and_description_follow_the_arabic_locale(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('<title>'.__('home.title').'</title>', false);
        $response->assertSee(__('common.seo.description'), false);
    }

    public function test_title_and_description_follow_the_english_locale(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('home'));

        $response->assertSee('<title>TTU Clinic - Home</title>', false);
        $response->assertSee('The online clinic of Tafila Technical University', false);
    }

    // ------------------------------------------------------------------
    // الهيدر — بلا زر "تواصل" بأي صفحة
    // ------------------------------------------------------------------

    public function test_header_has_no_contact_link_for_guests_or_students(): void
    {
        $this->get(route('home'))->assertDontSee('nav-link">'.__('common.nav.contact'), false);

        $response = $this->actingAs($this->student())->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertDontSee('nav-link-dark">'.__('common.nav.contact'), false);
    }
}
