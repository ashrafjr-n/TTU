<?php

namespace Tests\Feature;

use App\Support\ChatbotFlow;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * شجرة التنقّل الثابتة لويدجت الدعم — الانتقال بين الطبقات 1→2→3، وأن
 * الطبقة 3 هي النهاية، وأن هذا المسار كله لا يستدعي أي API.
 */
class ChatbotFlowTest extends TestCase
{
    public function test_layer_one_offers_the_three_topics_then_chat(): void
    {
        $menu = ChatbotFlow::nodes()[ChatbotFlow::MENU];

        $this->assertSame(1, $menu['layer']);
        $this->assertSame(__('chatbot.welcome'), $menu['message']);
        $this->assertCount(4, $menu['options']);

        // الثلاثة الأولى مواضيع تؤدي للطبقة 2، والرابع "محادثة"
        $this->assertSame([
            ChatbotFlow::topicNode('booking'),
            ChatbotFlow::topicNode('login'),
            ChatbotFlow::topicNode('contact_clinic'),
            ChatbotFlow::CHAT,
        ], array_column($menu['options'], 'target'));

        $this->assertSame(__('chatbot.options.chat'), $menu['options'][3]['label']);
    }

    public function test_layer_two_shows_the_short_answer_with_back_more_and_two_followups(): void
    {
        $nodes = ChatbotFlow::nodes();

        foreach (ChatbotFlow::TOPICS as $topic) {
            $node = $nodes[ChatbotFlow::topicNode($topic)];

            $this->assertSame(2, $node['layer'], "layer for {$topic}");
            $this->assertSame(__("chatbot.topics.{$topic}.short"), $node['message']);

            // رجوع + "أخبرني المزيد" + سؤالا متابعة = 4 خيارات بالضبط
            $this->assertCount(4, $node['options'], "options for {$topic}");

            $this->assertSame([
                ChatbotFlow::MENU,
                ChatbotFlow::detailNode($topic, 'more'),
                ChatbotFlow::detailNode($topic, 'followup_1'),
                ChatbotFlow::detailNode($topic, 'followup_2'),
            ], array_column($node['options'], 'target'), "targets for {$topic}");

            // نص زر المتابعة هو السؤال نفسه، ولكل سؤال جوابه الجاهز
            $followups = ChatbotFlow::followups($topic);
            $this->assertCount(2, $followups);
            $this->assertSame($followups[0]['question'], $node['options'][2]['label']);
            $this->assertSame($followups[1]['question'], $node['options'][3]['label']);
        }
    }

    public function test_layer_three_answers_offer_only_back_to_menu_and_chat(): void
    {
        $nodes = ChatbotFlow::nodes();

        foreach (ChatbotFlow::TOPICS as $topic) {
            $followups = ChatbotFlow::followups($topic);

            $expected = [
                'more' => __("chatbot.topics.{$topic}.detailed"),
                'followup_1' => $followups[0]['answer'],
                'followup_2' => $followups[1]['answer'],
            ];

            foreach ($expected as $key => $message) {
                $node = $nodes[ChatbotFlow::detailNode($topic, $key)];

                $this->assertSame(3, $node['layer'], "layer for {$topic}.{$key}");
                $this->assertSame($message, $node['message'], "message for {$topic}.{$key}");
                $this->assertSame(
                    [ChatbotFlow::MENU, ChatbotFlow::CHAT],
                    array_column($node['options'], 'target'),
                    "targets for {$topic}.{$key}"
                );
            }
        }
    }

    public function test_layer_three_is_the_deepest_layer(): void
    {
        $nodes = ChatbotFlow::nodes();

        // 1 قائمة + 3 مواضيع + (3 مواضيع × 3 أجوبة مفصّلة) = 13 عقدة
        $this->assertCount(13, $nodes);

        foreach ($nodes as $id => $node) {
            $this->assertLessThanOrEqual(3, $node['layer'], "layer for {$id}");

            foreach ($node['options'] as $option) {
                // لا هدف مجهول: كل خيار إما عقدة مطبوعة أصلًا أو وضع المحادثة
                $this->assertTrue(
                    $option['target'] === ChatbotFlow::CHAT || isset($nodes[$option['target']]),
                    "unknown target {$option['target']} in {$id}"
                );

                // ولا تعشيش أبعد من الطبقة 3
                if ($node['layer'] === 3 && $option['target'] !== ChatbotFlow::CHAT) {
                    $this->assertSame(ChatbotFlow::MENU, $option['target'], "escape from {$id}");
                }
            }
        }
    }

    public function test_flow_content_follows_the_active_locale(): void
    {
        app()->setLocale('ar');
        $ar = ChatbotFlow::nodes();

        app()->setLocale('en');
        $en = ChatbotFlow::nodes();

        $this->assertSame('مرحبًا بك في عيادة الجامعة — كيف يمكنني مساعدتك؟', $ar[ChatbotFlow::MENU]['message']);
        $this->assertSame('Welcome to the university clinic — how can I help you?', $en[ChatbotFlow::MENU]['message']);

        // لا مفتاح ترجمة ناقص بأي طبقة بأي لغة (وإلا رجع __() المفتاح نفسه)
        foreach ([$ar, $en] as $tree) {
            foreach ($tree as $id => $node) {
                $this->assertStringNotContainsString('chatbot.', $node['message'], "untranslated message in {$id}");

                foreach ($node['options'] as $option) {
                    $this->assertStringNotContainsString('chatbot.', $option['label'], "untranslated label in {$id}");
                }
            }
        }
    }

    public function test_walking_every_layer_never_calls_the_api(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        // زيارة الشجرة بالكامل من الطبقة 1 حتى كل أوراق الطبقة 3
        $nodes = ChatbotFlow::nodes();
        $visited = [];
        $queue = [ChatbotFlow::MENU];

        while ($queue) {
            $current = array_shift($queue);

            if ($current === ChatbotFlow::CHAT || isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            foreach ($nodes[$current]['options'] as $option) {
                $queue[] = $option['target'];
            }
        }

        $this->assertCount(13, $visited);

        Http::assertNothingSent();
    }
}
