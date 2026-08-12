<?php

namespace App\Support;

/**
 * شجرة التنقّل الثابتة لويدجت الدعم (الطبقات 1–3).
 *
 * كل المحتوى يأتي من ملفات lang/{locale}/chatbot.php، وتُبنى الشجرة هنا
 * كخريطة مسطّحة "معرّف العقدة => العقدة" بدل تعشيش، حتى يصير عمل الجافاسكربت
 * بالمتصفح مجرد `nodes[target]` — لا منطق تنقّل مكرر بالواجهة، ولا أي طلب
 * شبكة في هذا المسار كله (الطبقات تُطبع كاملة مع الصفحة).
 *
 * الطبقات:
 *   1  menu                       — الترحيب + 3 مواضيع + "محادثة"
 *   2  topic:<topic>              — جواب قصير + رجوع/المزيد/سؤالا متابعة
 *   3  detail:<topic>:<node>      — جواب مفصّل، وخيارَان فقط: رجوع + محادثة
 *
 * الطبقة 3 هي النهاية: لا تعشيش أبعد منها (يتحقق ChatbotFlowTest من ذلك).
 */
class ChatbotFlow
{
    /** معرّف عقدة القائمة الرئيسية (الطبقة 1). */
    public const MENU = 'menu';

    /** هدف خاص: التحويل لوضع المحادثة الحرة (المسار الوحيد الذي يستدعي الـAPI). */
    public const CHAT = 'chat';

    /** المواضيع الثلاثة — المفاتيح نفسها المستخدمة في ملفات lang. */
    public const TOPICS = ['booking', 'login', 'contact_doctor'];

    /**
     * @return array<string, array{layer:int, message:string, options:list<array{label:string, target:string}>}>
     */
    public static function nodes(): array
    {
        $nodes = [self::MENU => self::menuNode()];

        foreach (self::TOPICS as $topic) {
            $nodes[self::topicNode($topic)] = self::shortAnswerNode($topic);

            foreach (self::detailNodesFor($topic) as $id => $node) {
                $nodes[$id] = $node;
            }
        }

        return $nodes;
    }

    public static function topicNode(string $topic): string
    {
        return "topic:{$topic}";
    }

    public static function detailNode(string $topic, string $node): string
    {
        return "detail:{$topic}:{$node}";
    }

    /**
     * الطبقة 1 — الترحيب وأزرار المواضيع الثلاثة ثم "محادثة".
     */
    private static function menuNode(): array
    {
        $options = [];

        foreach (self::TOPICS as $topic) {
            $options[] = [
                'label' => __("chatbot.topics.{$topic}.label"),
                'target' => self::topicNode($topic),
            ];
        }

        $options[] = self::chatOption();

        return [
            'layer' => 1,
            'message' => __('chatbot.welcome'),
            'options' => $options,
        ];
    }

    /**
     * الطبقة 2 — الجواب القصير، ثم: رجوع للقائمة، "أخبرني المزيد"، وسؤالا
     * المتابعة الخاصان بالموضوع (وكلها ما عدا الرجوع تؤدي للطبقة 3).
     */
    private static function shortAnswerNode(string $topic): array
    {
        $options = [
            self::backOption(),
            [
                'label' => __('chatbot.options.tell_me_more'),
                'target' => self::detailNode($topic, 'more'),
            ],
        ];

        foreach (self::followups($topic) as $index => $followup) {
            $options[] = [
                'label' => $followup['question'],
                'target' => self::detailNode($topic, 'followup_'.($index + 1)),
            ];
        }

        return [
            'layer' => 2,
            'message' => __("chatbot.topics.{$topic}.short"),
            'options' => $options,
        ];
    }

    /**
     * الطبقة 3 — عقدة لكل جواب مفصّل ("المزيد" + جواب كل سؤال متابعة)،
     * وخيارَان فقط بكل واحدة: رجوع للقائمة، ومحادثة.
     *
     * @return array<string, array>
     */
    private static function detailNodesFor(string $topic): array
    {
        $options = [self::backOption(), self::chatOption()];

        $nodes = [
            self::detailNode($topic, 'more') => [
                'layer' => 3,
                'message' => __("chatbot.topics.{$topic}.detailed"),
                'options' => $options,
            ],
        ];

        foreach (self::followups($topic) as $index => $followup) {
            $nodes[self::detailNode($topic, 'followup_'.($index + 1))] = [
                'layer' => 3,
                'message' => $followup['answer'],
                'options' => $options,
            ];
        }

        return $nodes;
    }

    /**
     * @return list<array{question:string, answer:string}>
     */
    public static function followups(string $topic): array
    {
        $followups = __("chatbot.topics.{$topic}.followups");

        return is_array($followups) ? array_values($followups) : [];
    }

    private static function backOption(): array
    {
        return [
            'label' => __('chatbot.options.back_to_menu'),
            'target' => self::MENU,
        ];
    }

    private static function chatOption(): array
    {
        return [
            'label' => __('chatbot.options.chat'),
            'target' => self::CHAT,
        ];
    }
}
