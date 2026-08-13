{{--
    جرس الإشعارات + لوحته المنسدلة — مشترك بين ترويستَي الموقع
    (components/app-header و partials/auth-header) بعد أن صارتا تعرضان نفس
    القائمة تمامًا. الفرق الوحيد بينهما شكل الزر (شفاف فوق الهيرو أم بارز
    فوق الخلفية الكريمية)، فيُمرَّر كوسيطين.

    المعطيات:
      $iconBtnClass   — أصناف زر الأيقونة
      $iconColorClass — لون أيقونة الجرس
--}}
@auth
@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
    $recentNotifications = auth()->user()->notifications()->latest()->take(10)->get();

    // إشعارات "رد الإدارة" لا تحمل نص الرد ضمن بياناتها عمدًا (راجع
    // ClinicReplyReceived)، فيُجلب النص هنا من جدول الرسائل نفسه — استعلام
    // واحد لكل الإشعارات المعروضة، ومقصور على رسائل هذا المستخدم فلا يقدر
    // معرّف رسالة مزروع أن يكشف رسالة غيره.
    $replyIds = $recentNotifications
        ->filter(fn ($n) => ($n->data['type'] ?? null) === 'clinic_reply')
        ->pluck('data.message_id')
        ->filter()
        ->all();

    $replyMessages = $replyIds
        ? \App\Models\Message::with('parent')
            ->whereIn('id', $replyIds)
            ->where('recipient_id', auth()->id())
            ->get()
            ->keyBy('id')
        : collect();
@endphp

<div class="relative">
    <button type="button" id="notif-toggle" title="{{ __('common.header.notifications') }}" aria-label="{{ __('common.header.notifications') }}"
            class="{{ $iconBtnClass }} relative w-10 h-10 flex items-center justify-center rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if ($unreadCount > 0)
            <span id="notif-badge" class="absolute -top-1 -left-1 min-w-[16px] h-4 px-1 rounded-full bg-ttu-red text-white text-[10px] font-bold flex items-center justify-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div id="notif-panel"
         class="hidden absolute top-full start-0 mt-3 w-80 max-w-[90vw] rounded-2xl neu-raised-white p-3 z-50">
        <div class="flex items-center justify-between px-1 pb-2 mb-2 border-b border-black/10 dark:border-white/10">
            <span class="text-sm font-bold text-ttu-black">{{ __('common.header.notifications') }}</span>
            @if ($unreadCount > 0)
                <button type="button" id="notif-mark-all" class="text-xs font-bold text-ttu-red hover:underline">
                    {{ __('common.header.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto space-y-2">
            @forelse ($recentNotifications as $n)
                @php
                    // رد الإدارة: العنصر يفتح لوحة الرسالة داخل الصفحة بدل
                    // الانتقال لرابط، وسياق المحادثة يُمرَّر عبر data-*
                    $reply = ($n->data['type'] ?? null) === 'clinic_reply'
                        ? $replyMessages->get($n->data['message_id'] ?? null)
                        : null;
                @endphp

                <div class="notif-item rounded-xl px-3 py-2.5 cursor-pointer transition neu-pressed {{ $n->read_at ? 'opacity-60' : '' }}"
                     data-id="{{ $n->id }}"
                     data-url="{{ $n->data['url'] ?? '' }}"
                     data-read="{{ $n->read_at ? '1' : '0' }}"
                     @if ($reply)
                         data-panel="1"
                         data-panel-title="{{ __('message_panel.heading') }}"
                         data-reply-sender="{{ $reply->sender->name }}"
                         data-reply-body="{{ $reply->body }}"
                         data-reply-time="{{ $reply->created_at->translatedFormat('d F Y — H:i') }}"
                         data-original-body="{{ $reply->parent?->body }}"
                         data-original-time="{{ $reply->parent?->created_at->translatedFormat('d F Y — H:i') }}"
                     @endif>
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-bold text-ttu-black">{{ isset($n->data['title_key']) ? __($n->data['title_key'], $n->data['title_params'] ?? []) : ($n->data['title'] ?? '') }}</p>
                        @if (!$n->read_at)
                            <span class="notif-dot w-2 h-2 rounded-full bg-ttu-red mt-1 shrink-0"></span>
                        @endif
                    </div>
                    <p class="text-xs text-ttu-gray mt-1 leading-relaxed">{{ isset($n->data['body_key']) ? __($n->data['body_key'], $n->data['body_params'] ?? []) : ($n->data['body'] ?? '') }}</p>
                    <p class="text-[10px] text-ttu-gray/70 mt-1.5">{{ $n->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-xs text-ttu-gray text-center py-6">{{ __('common.header.no_notifications') }}</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    const notifMarkReadTemplate = "{{ route('notifications.read', ['notification' => '__ID__']) }}";
    const notifMarkAllUrl = "{{ route('notifications.read-all') }}";

    function notifCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function notifUpdateBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (count <= 0) {
            if (badge) badge.remove();
        } else if (badge) {
            badge.textContent = count > 9 ? '9+' : count;
        }
    }

    function notifMarkItemRead(item) {
        if (item.dataset.read !== '0') return;

        item.dataset.read = '1';
        item.classList.add('opacity-60');
        const dot = item.querySelector('.notif-dot');
        if (dot) dot.remove();

        fetch(notifMarkReadTemplate.replace('__ID__', item.dataset.id), {
            method: 'POST',
            keepalive: true,
            headers: {
                'X-CSRF-TOKEN': notifCsrfToken(),
                'Accept': 'application/json',
            },
        })
            .then((res) => res.json())
            .then((data) => notifUpdateBadge(data.unread_count))
            .catch(() => {});
    }

    function notifMarkAllRead() {
        document.querySelectorAll('.notif-item').forEach((item) => {
            item.dataset.read = '1';
            item.classList.add('opacity-60');
            const dot = item.querySelector('.notif-dot');
            if (dot) dot.remove();
        });

        const markAllBtn = document.getElementById('notif-mark-all');
        if (markAllBtn) markAllBtn.remove();

        fetch(notifMarkAllUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': notifCsrfToken(),
                'Accept': 'application/json',
            },
        })
            .then((res) => res.json())
            .then((data) => notifUpdateBadge(data.unread_count))
            .catch(() => {});
    }

    document.addEventListener('click', function (e) {
        const notifToggle = document.getElementById('notif-toggle');
        const notifPanel = document.getElementById('notif-panel');
        if (notifToggle && notifPanel) {
            if (notifToggle.contains(e.target)) {
                notifPanel.classList.toggle('hidden');
            } else if (!notifPanel.contains(e.target)) {
                notifPanel.classList.add('hidden');
            }
        }

        if (e.target.id === 'notif-mark-all') {
            notifMarkAllRead();
            return;
        }

        const item = e.target.closest('.notif-item');
        if (item) {
            notifMarkItemRead(item);

            // رد الإدارة يُقرأ داخل لوحة بالصفحة نفسها، لا بالانتقال لرابط
            if (item.dataset.panel === '1' && typeof openMessagePanel === 'function') {
                if (notifPanel) notifPanel.classList.add('hidden');
                openMessagePanel(item.dataset);
                return;
            }

            if (item.dataset.url) {
                window.location.href = item.dataset.url;
            }
        }
    });
</script>
@endauth
