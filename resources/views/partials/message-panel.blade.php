{{--
    لوحة قراءة رد الإدارة — تُفتح داخل الصفحة عند الضغط على إشعار "لديك رد
    من إدارة العيادة" (لا انتقال لصفحة أخرى)، لأن نص الرد لا يظهر في قائمة
    الإشعارات إطلاقًا.

    الشكل "إطار نافذة": شريط علوي بنقاط ولافتة العيادة، ثم المحتوى داخله —
    الرسالة الأصلية كسياق أولًا ثم الرد. الرد من داخل اللوحة غير متاح عمدًا:
    زر "رد" ينقل لصفحة "تواصل" حيث تُكتب رسالة جديدة بنفس المسار المعتاد.

    مرفوعة على مستوى التخطيط لتكون متاحة لأي صفحة فيها جرس الإشعارات
    (الترويستان معًا)، لا مكرّرة في كل واحدة.
--}}
@auth
<div id="messagePanelOverlay"
     class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/40 backdrop-blur-sm px-4 py-8"
     role="dialog" aria-modal="true" aria-labelledby="messagePanelHeading">

    <div id="messagePanelCard"
         class="relative w-full max-w-lg max-h-full flex flex-col rounded-[2rem] neu-raised-white overflow-hidden transition-all duration-300 scale-95 opacity-0">

        {{-- شريط الإطار العلوي --}}
        <div class="flex items-center gap-3 px-5 py-3.5 border-b border-black/10 dark:border-white/10">
            <span class="flex items-center gap-1.5 shrink-0" aria-hidden="true">
                <span class="w-2.5 h-2.5 rounded-full bg-ttu-red/70"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-ttu-gray/30"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-ttu-gray/30"></span>
            </span>

            <span class="flex-1 min-w-0 flex items-center justify-center gap-2 rounded-full neu-pressed px-4 py-1.5">
                <svg class="w-3.5 h-3.5 text-ttu-red shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                </svg>
                <span class="text-[11px] font-bold text-ttu-gray truncate">{{ __('message_panel.frame_label') }}</span>
            </span>

            <button type="button" onclick="closeMessagePanel()" title="{{ __('message_panel.close') }}" aria-label="{{ __('message_panel.close') }}"
                    class="shrink-0 w-8 h-8 rounded-full neu-icon-btn bg-ttu-cream text-ttu-gray flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- محتوى الرسالة --}}
        <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-6">

            <div class="flex items-center gap-4 mb-6">
                <span class="w-12 h-12 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="messagePanelHeading" class="font-display text-lg font-extrabold leading-tight">{{ __('message_panel.heading') }}</h3>
                    <p id="messagePanelSender" class="text-xs text-ttu-gray mt-0.5 truncate"></p>
                </div>
            </div>

            {{-- الرسالة الأصلية كسياق — تُخفى لو تعذّر جلبها --}}
            <div id="messagePanelOriginal" class="hidden mb-5">
                <p class="text-[11px] font-bold text-ttu-gray tracking-widest mb-2">{{ __('message_panel.original_label') }}</p>
                <div class="rounded-2xl neu-pressed px-5 py-4">
                    <p id="messagePanelOriginalBody" class="text-sm text-ttu-gray leading-relaxed whitespace-pre-line"></p>
                    <p id="messagePanelOriginalTime" class="text-[10px] text-ttu-gray/70 mt-2.5"></p>
                </div>
            </div>

            {{-- الرد نفسه --}}
            <div>
                <p class="text-[11px] font-bold text-ttu-red tracking-widest mb-2">{{ __('message_panel.reply_label') }}</p>
                <div class="rounded-2xl neu-pressed px-5 py-4 border-s-4 border-ttu-red">
                    <p id="messagePanelReplyBody" class="text-sm text-ttu-black leading-relaxed whitespace-pre-line"></p>
                    <p id="messagePanelReplyTime" class="text-[10px] text-ttu-gray/70 mt-2.5"></p>
                </div>
            </div>

        </div>

        {{-- الأزرار — "رد" ينقل لصفحة تواصل، لا رد من داخل اللوحة --}}
        <div class="flex flex-col sm:flex-row gap-3 px-6 sm:px-8 py-5 border-t border-black/10 dark:border-white/10">
            <button type="button" onclick="closeMessagePanel()"
                    class="flex-1 neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold py-3 rounded-xl">
                {{ __('message_panel.close') }}
            </button>

            @if (auth()->user()->isStudent() || auth()->user()->isStaff())
                <a href="{{ route('contact') }}"
                   class="flex-1 neu-icon-btn bg-ttu-red text-white text-sm font-bold py-3 rounded-xl hover:!bg-ttu-red-dark flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                    {{ __('message_panel.reply_button') }}
                </a>
            @endif
        </div>

    </div>
</div>

<script>
    function openMessagePanel(data) {
        const overlay = document.getElementById('messagePanelOverlay');
        const card = document.getElementById('messagePanelCard');
        if (!overlay || !card) return;

        document.getElementById('messagePanelSender').textContent = data.replySender || '';
        document.getElementById('messagePanelReplyBody').textContent = data.replyBody || '';
        document.getElementById('messagePanelReplyTime').textContent = data.replyTime || '';

        const original = document.getElementById('messagePanelOriginal');
        if (data.originalBody) {
            document.getElementById('messagePanelOriginalBody').textContent = data.originalBody;
            document.getElementById('messagePanelOriginalTime').textContent = data.originalTime || '';
            original.classList.remove('hidden');
        } else {
            original.classList.add('hidden');
        }

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');

        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    }

    function closeMessagePanel() {
        const overlay = document.getElementById('messagePanelOverlay');
        const card = document.getElementById('messagePanelCard');
        if (!overlay || !card) return;

        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }, 200);
    }

    (function () {
        const overlay = document.getElementById('messagePanelOverlay');
        if (!overlay) return;

        overlay.addEventListener('click', function (e) {
            if (e.target === this) closeMessagePanel();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeMessagePanel();
        });
    })();
</script>
@endauth
