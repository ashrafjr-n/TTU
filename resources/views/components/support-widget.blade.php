{{--
    ويدجت الدعم — زر دائري عائم أسفل يمين الشاشة بكل الصفحات، يفتح لوحة
    بأسلوب محادثة. كل المحتوى (الطبقات 1–3) ثابت ومترجم ويُطبع هنا مع الصفحة،
    فالتنقّل بينها لا يمس الشبكة أبدًا؛ خيار "محادثة" وحده يستدعي الخادم.

    شجرة العقد تُبنى في App\Support\ChatbotFlow، فيبقى منطق التنقّل بمكان
    واحد قابل للاختبار، ويصير عمل الجافاسكربت هنا مجرد عرض العقدة المطلوبة.
--}}

@php
    $supportWidget = [
        'nodes' => \App\Support\ChatbotFlow::nodes(),
        'menu' => \App\Support\ChatbotFlow::MENU,
        'chat' => \App\Support\ChatbotFlow::CHAT,
        'endpoint' => route('chatbot.message'),
        'ui' => [
            'chat_intro' => __('chatbot.chat.intro'),
            'unavailable' => __('chatbot.chat.unavailable'),
            'back_to_menu' => __('chatbot.options.back_to_menu'),
        ],
    ];
@endphp

<div id="support-widget" class="fixed bottom-5 right-5 z-[200] print:hidden">

    {{-- اللوحة — تُفتح مكانها بأسفل يمين الشاشة دون مغادرة الصفحة الحالية --}}
    <div id="support-widget-panel"
         class="hidden absolute bottom-full right-0 mb-4 w-[22rem] max-w-[calc(100vw-2.5rem)] rounded-3xl neu-raised-white p-4"
         role="dialog" aria-modal="false" aria-labelledby="support-widget-title">

        <div class="flex items-start justify-between gap-3 pb-3 mb-3 border-b border-black/10 dark:border-white/10">
            <div class="min-w-0">
                <p id="support-widget-title" class="text-sm font-bold text-ttu-black">{{ __('chatbot.widget.title') }}</p>
                <p class="text-[11px] text-ttu-gray mt-0.5">{{ __('chatbot.widget.subtitle') }}</p>
            </div>
            <button type="button" id="support-widget-close"
                    title="{{ __('chatbot.widget.close') }}" aria-label="{{ __('chatbot.widget.close') }}"
                    class="neu-icon-btn bg-ttu-cream shrink-0 w-8 h-8 flex items-center justify-center rounded-full">
                <svg class="w-4 h-4 text-ttu-black" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- سجل الرسائل --}}
        <div id="support-widget-messages" class="max-h-[19rem] overflow-y-auto space-y-2.5 pe-1" aria-live="polite"></div>

        {{-- أزرار الخيارات (الطبقات الثابتة) --}}
        <div id="support-widget-options" class="mt-3 space-y-2"></div>

        {{-- وضع المحادثة الحرة --}}
        <form id="support-widget-chat" class="hidden mt-3">
            <div class="flex items-center gap-2">
                <input type="text" id="support-widget-input" autocomplete="off" maxlength="500"
                       placeholder="{{ __('chatbot.chat.placeholder') }}"
                       class="flex-1 min-w-0 rounded-xl neu-pressed bg-ttu-cream border-0 px-3.5 py-2.5 text-xs text-ttu-black placeholder:text-ttu-gray/80 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                <button type="submit" id="support-widget-send"
                        class="shrink-0 rounded-xl bg-ttu-red text-white text-xs font-bold px-3.5 py-2.5 disabled:opacity-60">
                    {{ __('chatbot.chat.send') }}
                </button>
            </div>
            <button type="button" id="support-widget-chat-back"
                    class="mt-2 w-full neu-icon-btn bg-ttu-cream rounded-xl px-3.5 py-2 text-[11px] font-bold text-ttu-gray">
                {{ __('chatbot.options.back_to_menu') }}
            </button>
        </form>

    </div>

    {{--
        الزر العائم — حالتان بصريًا:
        • مغلق: أنيميشن Lottie وحده بلا أي خلفية أو ظل (شفاف تمامًا).
        • مفتوح: نفس أيقونة الإغلاق (X) كما هي، وتُضاف خلفية الزر وظله
          بدرجة أغمق قليلًا (ttu-red-dark) — الأصناف تُبدَّل من setOpen().
    --}}
    <button type="button" id="support-widget-toggle"
            title="{{ __('chatbot.widget.open') }}" aria-label="{{ __('chatbot.widget.open') }}"
            aria-expanded="false" aria-controls="support-widget-panel"
            class="support-fab w-14 h-14 rounded-full text-white flex items-center justify-center ms-auto">

        <span id="support-widget-icon-open" class="block w-full h-full">
            {{-- حاوية الأنيميشن — بلا خلفية، يملؤها Lottie كـSVG شفاف --}}
            <span id="support-widget-lottie" class="block w-full h-full" aria-hidden="true"></span>

            {{-- بديل يظهر فقط لو تعذّر تحميل مكتبة Lottie من الـCDN، حتى لا
                 يبقى الزر فارغًا بلا أي أيقونة --}}
            <svg id="support-widget-icon-fallback" class="hidden w-6 h-6 m-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.556 0 8.25-3.19 8.25-7.125S16.556 6 12 6s-8.25 3.19-8.25 7.125c0 1.85.816 3.535 2.153 4.8.09.653-.132 1.503-.51 2.19-.203.371.023.865.44.803a7.87 7.87 0 003.037-1.242A9.66 9.66 0 0012 20.25z" />
            </svg>
        </span>

        <svg id="support-widget-icon-close" class="hidden w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

</div>

{{--
    محتوى الطبقات الثابتة كاملًا — يُطبع مع الصفحة كـJSON فيقرأه الجافاسكربت
    محليًا بلا أي طلب. JSON_HEX_TAG يمنع أي "‎</script>" داخل النص من كسر الوسم،
    وJSON_UNESCAPED_UNICODE يُبقي العربية مقروءة بمصدر الصفحة كما هي.
--}}
<script type="application/json" id="support-widget-data">
    {!! json_encode($supportWidget, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>

<script>
    (function () {
        const dataEl = document.getElementById('support-widget-data');
        if (!dataEl) return;

        const config = JSON.parse(dataEl.textContent);

        const widget = document.getElementById('support-widget');
        const panel = document.getElementById('support-widget-panel');
        const toggle = document.getElementById('support-widget-toggle');
        const closeBtn = document.getElementById('support-widget-close');
        const messages = document.getElementById('support-widget-messages');
        const options = document.getElementById('support-widget-options');
        const chatForm = document.getElementById('support-widget-chat');
        const chatInput = document.getElementById('support-widget-input');
        const sendBtn = document.getElementById('support-widget-send');
        const chatBack = document.getElementById('support-widget-chat-back');
        const iconOpen = document.getElementById('support-widget-icon-open');
        const iconClose = document.getElementById('support-widget-icon-close');

        if (!widget || !panel) return;

        let started = false;

        function scrollToLatest() {
            messages.scrollTop = messages.scrollHeight;
        }

        // textContent دائمًا — رد المحادثة نص خارجي، فلا يُحقن كـHTML أبدًا
        function addMessage(text, from) {
            const row = document.createElement('div');
            row.className = from === 'user' ? 'flex justify-end' : 'flex justify-start';

            const bubble = document.createElement('div');
            bubble.className = from === 'user'
                ? 'max-w-[85%] rounded-2xl bg-ttu-red text-white px-3.5 py-2.5 text-xs leading-relaxed'
                : 'max-w-[92%] rounded-2xl neu-pressed bg-ttu-cream text-ttu-black px-3.5 py-2.5 text-xs leading-relaxed';
            bubble.textContent = text;

            row.appendChild(bubble);
            messages.appendChild(row);
            scrollToLatest();

            return row;
        }

        function addTyping() {
            const row = document.createElement('div');
            row.className = 'flex justify-start';
            row.innerHTML = '<div class="rounded-2xl neu-pressed bg-ttu-cream px-4 py-3 flex items-center gap-1.5">'
                + '<span class="loading-dot w-1.5 h-1.5 rounded-full bg-ttu-red"></span>'
                + '<span class="loading-dot loading-dot-2 w-1.5 h-1.5 rounded-full bg-ttu-red"></span>'
                + '<span class="loading-dot loading-dot-3 w-1.5 h-1.5 rounded-full bg-ttu-red"></span></div>';
            messages.appendChild(row);
            scrollToLatest();

            return row;
        }

        function renderOptions(nodeOptions) {
            options.innerHTML = '';

            nodeOptions.forEach(function (option) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'neu-icon-btn bg-ttu-cream w-full text-start rounded-xl px-3.5 py-2.5 text-xs font-semibold text-ttu-black';
                button.textContent = option.label;
                button.addEventListener('click', function () {
                    addMessage(option.label, 'user');
                    goTo(option.target);
                });
                options.appendChild(button);
            });
        }

        /** الانتقال بين الطبقات — بحث في الشجرة المطبوعة، بلا أي طلب شبكة */
        function goTo(target) {
            if (target === config.chat) {
                enterChatMode();
                return;
            }

            const node = config.nodes[target];
            if (!node) return;

            leaveChatMode();
            addMessage(node.message, 'bot');
            renderOptions(node.options);
        }

        function enterChatMode() {
            options.classList.add('hidden');
            chatForm.classList.remove('hidden');
            addMessage(config.ui.chat_intro, 'bot');
            chatInput.focus();
        }

        function leaveChatMode() {
            chatForm.classList.add('hidden');
            options.classList.remove('hidden');
        }

        function start() {
            if (started) return;
            started = true;
            goTo(config.menu);
        }

        function setOpen(open) {
            panel.classList.toggle('hidden', !open);
            iconOpen.classList.toggle('hidden', open);
            iconClose.classList.toggle('hidden', !open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

            // الخلفية والظل للحالة المفتوحة فقط: المغلقة تعرض الأنيميشن وحده
            // بلا أي حاوية خلفه. الدرجة هنا أغمق قليلًا من ttu-red السابقة.
            //
            // استثناء: لو تعذّر تحميل Lottie وظهرت الأيقونة البديلة، فهي بيضاء
            // بلا خلفية — أي غير مرئية على الخلفية الفاتحة. عندها يعود الزر
            // لشكله السابق تمامًا (دائرة + ظل) حتى يبقى ظاهرًا.
            const fallback = document.getElementById('support-widget-icon-fallback');
            const usingFallback = fallback && !fallback.classList.contains('hidden');

            toggle.classList.toggle('neu-fab', open || usingFallback);
            toggle.classList.toggle('bg-ttu-red-dark', open);
            toggle.classList.toggle('bg-ttu-red', !open && usingFallback);

            if (open) start();
        }

        toggle.addEventListener('click', function () {
            setOpen(panel.classList.contains('hidden'));
        });

        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });

        chatBack.addEventListener('click', function () {
            addMessage(config.ui.back_to_menu, 'user');
            goTo(config.menu);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.classList.contains('hidden')) setOpen(false);
        });

        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const text = chatInput.value.trim();
            if (!text) return;

            chatInput.value = '';
            addMessage(text, 'user');
            sendBtn.disabled = true;
            const typing = addTyping();

            const csrf = document.querySelector('meta[name="csrf-token"]');

            fetch(config.endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: text }),
            })
                .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
                .then(function (data) {
                    typing.remove();
                    addMessage(data.reply || config.ui.unavailable, 'bot');
                })
                .catch(function () {
                    // أي فشل شبكة/حد طلبات — الرسالة الثابتة، لا خطأ خام ولا واجهة معطوبة
                    typing.remove();
                    addMessage(config.ui.unavailable, 'bot');
                })
                .then(function () {
                    sendBtn.disabled = false;
                    chatInput.focus();
                });
        });
    })();
</script>

{{--
    أنيميشن أيقونة الزر (Lottie) — يُحمَّل من cdnjs كما تُحمَّل بقية المكتبات
    الخارجية بهذا المشروع. نسخة lottie_light كافية: مُصيِّر SVG فقط (وهو
    المطلوب هنا) وحجمها أصغر من الحزمة الكاملة.

    الملف نفسه محلي تحت public/animation، ويُصيَّر شفافًا بلا خلفية — لذلك
    الحالة المغلقة للزر بلا خلفية ولا ظل، فيبدو الروبوت طائرًا بمكانه.

    أي فشل بالتحميل (شبكة/حجب CDN) يُظهر أيقونة المحادثة البديلة بدل ترك
    الزر فارغًا.
--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie_light.min.js" defer></script>
<script>
    (function () {
        function showFallback() {
            const fallback = document.getElementById('support-widget-icon-fallback');
            const toggle = document.getElementById('support-widget-toggle');
            if (!fallback || !toggle) return;

            fallback.classList.remove('hidden');

            // الأيقونة البديلة بيضاء، فتحتاج خلفية الزر السابقة لتبقى مرئية
            toggle.classList.add('neu-fab', 'bg-ttu-red');
        }

        function initLottie() {
            const mount = document.getElementById('support-widget-lottie');
            if (!mount) return;

            if (typeof lottie === 'undefined') {
                showFallback();
                return;
            }

            try {
                lottie.loadAnimation({
                    container: mount,
                    renderer: 'svg',
                    loop: true,
                    autoplay: true,
                    path: '{{ asset('animation/AI-chatbot.json') }}',
                    rendererSettings: {
                        preserveAspectRatio: 'xMidYMid meet',
                        // الأنيميشن نفسه بلا خلفية — نتركه شفافًا كما هو
                        className: 'support-widget-lottie-svg',
                    },
                }).addEventListener('data_failed', showFallback);
            } catch (e) {
                showFallback();
            }
        }

        // سكربت الـCDN مُؤجَّل (defer) فينتهي تنفيذه قبل DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLottie);
        } else {
            initLottie();
        }
    })();
</script>
