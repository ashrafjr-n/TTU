<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ويدجت الدعم مضاف على مستوى layouts/main، فالمتوقع ظهوره بكل الصفحات
 * (عامة ومحمية) بنصوص تتبع لغة الموقع، ودون أي طلب API عند تحميل الصفحة.
 */
class SupportWidgetRenderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * النص كما يظهر فعليًا داخل جزيرة الـJSON بالصفحة — نفس أعلام json_encode
     * المستخدمة في المكوّن، منزوعةً منها علامتا التنصيص المحيطتان.
     */
    private function asPrinted(string $text): string
    {
        return trim(json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP), '"');
    }

    public function test_widget_appears_on_public_pages(): void
    {
        foreach ([route('home'), route('about'), route('login')] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $response->assertSee('id="support-widget"', false);
            $response->assertSee($this->asPrinted(__('chatbot.welcome')), false);
            $response->assertSee(route('chatbot.message'), false);
        }
    }

    public function test_widget_appears_on_authenticated_pages(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        foreach ([route('dashboard.student'), route('booking.index'), route('contact')] as $url) {
            $response = $this->actingAs($student)->get($url);

            $response->assertOk();
            $response->assertSee('id="support-widget"', false);
            $response->assertSee($this->asPrinted(__('chatbot.welcome')), false);
        }
    }

    public function test_widget_text_switches_with_the_site_locale(): void
    {
        $ar = $this->get(route('home'));
        $ar->assertOk();
        $ar->assertSee('مرحبًا بك في عيادة الجامعة — كيف يمكنني مساعدتك؟', false);
        $ar->assertSee('كيف أحجز موعدًا؟', false);
        $ar->assertSee('مساعد العيادة', false);

        $en = $this->withSession(['locale' => 'en'])->get(route('home'));
        $en->assertOk();
        $en->assertSee('Welcome to the university clinic — how can I help you?', false);
        $en->assertSee('How do I book an appointment?', false);
        $en->assertSee('Clinic support', false);
        $en->assertDontSee('كيف أحجز موعدًا؟', false);
        $en->assertDontSee('مساعد العيادة', false);
    }

    /**
     * الطبقات الثابتة تُطبع كاملة مع الصفحة (الأجوبة القصيرة والمفصّلة وأسئلة
     * المتابعة)، وهذا بالضبط ما يجعل التنقّل بينها بلا أي طلب للخادم.
     */
    public function test_all_static_answers_are_printed_into_the_page(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('home'));

        $response->assertOk();

        foreach (['booking', 'login', 'contact_doctor'] as $topic) {
            $response->assertSee($this->asPrinted(__("chatbot.topics.{$topic}.label")), false);
            $response->assertSee($this->asPrinted(__("chatbot.topics.{$topic}.short")), false);
            $response->assertSee($this->asPrinted(__("chatbot.topics.{$topic}.detailed")), false);

            foreach (__("chatbot.topics.{$topic}.followups") as $followup) {
                $response->assertSee($this->asPrinted($followup['question']), false);
                $response->assertSee($this->asPrinted($followup['answer']), false);
            }
        }
    }

    public function test_loading_pages_never_calls_the_api(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $student = User::factory()->create(['role' => 'student']);

        $this->get(route('home'))->assertOk();
        $this->get(route('about'))->assertOk();
        $this->actingAs($student)->get(route('booking.index'))->assertOk();

        Http::assertNothingSent();
    }
}
