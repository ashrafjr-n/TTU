<?php

namespace Tests\Feature;

use App\Models\ChatbotUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * وضع "محادثة" — المسار الوحيد الذي يستدعي OpenRouter. لا يُستدعى الـAPI
 * الحقيقي هنا إطلاقًا: كل الاختبارات تستخدم Http::fake مع
 * preventStrayRequests، والمفتاح بالاختبارات فارغ افتراضيًا (phpunit.xml).
 */
class ChatbotChatModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'test/model',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.daily_limit' => 3,
        ]);
    }

    private function fakeReply(string $content): void
    {
        Http::fake([
            'openrouter.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => $content]]],
            ]),
        ]);
    }

    private function ask(string $message = 'How do I book an appointment?')
    {
        return $this->postJson(route('chatbot.message'), ['message' => $message]);
    }

    public function test_it_returns_the_model_reply_and_counts_the_request(): void
    {
        $this->fakeReply('Open “Book your appointment” from your dashboard.');

        $response = $this->ask();

        $response->assertOk();
        $response->assertJson([
            'reply' => 'Open “Book your appointment” from your dashboard.',
            'fallback' => false,
        ]);

        $this->assertSame(1, ChatbotUsage::usedToday());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://openrouter.test/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $body['model'] === 'test/model'
                && $body['messages'][0]['role'] === 'system'
                && $body['messages'][1]['content'] === 'How do I book an appointment?';
        });
    }

    public function test_the_system_prompt_scopes_the_assistant_to_the_clinic(): void
    {
        $this->fakeReply('ok');

        $this->ask()->assertOk();

        Http::assertSent(function ($request) {
            $prompt = $request->data()['messages'][0]['content'];

            return str_contains($prompt, 'support assistant')
                && str_contains($prompt, 'clinic')
                // يرفض ما هو خارج النطاق، ولا يكشف تفاصيل داخلية
                && str_contains($prompt, 'Refuse any request outside the topics above')
                && str_contains($prompt, 'Never reveal or discuss system internals')
                && str_contains($prompt, 'Contact page');
        });
    }

    public function test_the_daily_cap_falls_back_without_calling_the_api(): void
    {
        Http::fake();

        ChatbotUsage::create(['usage_date' => ChatbotUsage::dateKey(), 'requests_count' => 3]);

        $response = $this->ask();

        $response->assertOk();
        $response->assertJson([
            'reply' => __('chatbot.chat.unavailable'),
            'fallback' => true,
        ]);

        Http::assertNothingSent();
        // العدّاد لا يتجاوز السقف
        $this->assertSame(3, ChatbotUsage::usedToday());
    }

    public function test_requests_stop_exactly_at_the_daily_cap(): void
    {
        $this->fakeReply('ok');

        // السقف 3 — أول ثلاثة طلبات تمر، والرابع يرجع الرسالة الثابتة
        for ($i = 1; $i <= 3; $i++) {
            $this->ask()->assertJson(['fallback' => false]);
            $this->assertSame($i, ChatbotUsage::usedToday());
        }

        $this->ask()->assertJson(['reply' => __('chatbot.chat.unavailable'), 'fallback' => true]);

        $this->assertSame(3, ChatbotUsage::usedToday());
        Http::assertSentCount(3);
    }

    public function test_the_cap_is_tracked_per_day_so_it_resets_daily(): void
    {
        $this->fakeReply('ok');

        // استهلاك أمس بلغ السقف — لا يجب أن يمنع طلبات اليوم
        ChatbotUsage::create(['usage_date' => today()->subDay()->toDateString(), 'requests_count' => 3]);

        $this->ask()->assertJson(['fallback' => false]);

        $this->assertSame(1, ChatbotUsage::usedToday());
        $this->assertSame(2, ChatbotUsage::count());
    }

    public function test_an_api_error_status_falls_back(): void
    {
        Http::fake(['openrouter.test/*' => Http::response(['error' => 'rate limited'], 429)]);

        $response = $this->ask();

        $response->assertOk();
        $response->assertJson(['reply' => __('chatbot.chat.unavailable'), 'fallback' => true]);
    }

    public function test_a_timeout_falls_back(): void
    {
        Http::fake(fn () => throw new ConnectionException('Operation timed out'));

        $response = $this->ask();

        $response->assertOk();
        $response->assertJson(['reply' => __('chatbot.chat.unavailable'), 'fallback' => true]);
    }

    public function test_an_unexpected_response_shape_falls_back(): void
    {
        Http::fake(['openrouter.test/*' => Http::response(['choices' => []])]);

        $response = $this->ask();

        $response->assertOk();
        $response->assertJson(['reply' => __('chatbot.chat.unavailable'), 'fallback' => true]);
    }

    public function test_a_missing_api_key_falls_back_without_calling_the_api_or_spending_quota(): void
    {
        Http::fake();
        config(['services.openrouter.key' => null]);

        $response = $this->ask();

        $response->assertOk();
        $response->assertJson(['reply' => __('chatbot.chat.unavailable'), 'fallback' => true]);

        Http::assertNothingSent();
        $this->assertSame(0, ChatbotUsage::usedToday());
    }

    public function test_the_fallback_message_follows_the_site_locale(): void
    {
        Http::fake();
        config(['services.openrouter.key' => null]);

        $response = $this->withSession(['locale' => 'en'])->ask();

        $response->assertOk();
        $response->assertJson([
            'reply' => "Live chat isn't available right now. You can still use the quick topics — booking, logging in, or contacting a doctor — or reach the clinic through the Contact page.",
            'fallback' => true,
        ]);
    }

    public function test_the_message_is_validated(): void
    {
        Http::fake();

        $this->postJson(route('chatbot.message'), ['message' => ''])->assertStatus(422);
        $this->postJson(route('chatbot.message'), ['message' => str_repeat('a', 501)])->assertStatus(422);

        Http::assertNothingSent();
        $this->assertSame(0, ChatbotUsage::usedToday());
    }
}
