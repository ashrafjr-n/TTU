@props(['transparent' => false])

@php
    $headerClass = $transparent
        ? 'absolute top-0 inset-x-0 z-50 bg-white/0'
        : 'sticky top-0 z-50 bg-ttu-cream/90 backdrop-blur border-b border-black/5 dark:border-white/10';

    $heightClass = $transparent ? 'h-24' : 'h-20';
    $iconBtnClass = $transparent ? 'glass-icon-btn' : 'neu-icon-btn bg-ttu-cream';
    $iconColorClass = $transparent ? 'text-white' : 'text-ttu-black';
    $navLinkClass = $transparent ? 'nav-link' : 'nav-link-dark';

    $unreadCount = 0;
    $recentNotifications = collect();
@endphp

@auth
    @php
        $unreadCount = auth()->user()->unreadNotifications()->count();
        $recentNotifications = auth()->user()->notifications()->latest()->take(10)->get();
    @endphp
@endauth

<header class="{{ $headerClass }}">
    <div class="max-w-7xl mx-auto px-6 {{ $heightClass }} flex items-center justify-between gap-6">

        <div class="flex items-center gap-8">

            {{-- الأيقونات --}}
            <div class="flex items-center gap-2">

                {{-- الإشعارات --}}
                @auth
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
                                <div class="notif-item rounded-xl px-3 py-2.5 cursor-pointer transition neu-pressed {{ $n->read_at ? 'opacity-60' : '' }}"
                                     data-id="{{ $n->id }}"
                                     data-url="{{ $n->data['url'] ?? '' }}"
                                     data-read="{{ $n->read_at ? '1' : '0' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-xs font-bold text-ttu-black">{{ isset($n->data['title_key']) ? __($n->data['title_key'], $n->data['title_params'] ?? []) : ($n->data['title'] ?? '') }}</p>
                                        @if (!$n->read_at)
                                            <span class="notif-dot w-2 h-2 rounded-full bg-ttu-red mt-1 shrink-0"></span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-ttu-gray mt-1 leading-relaxed">{{ isset($n->data['body_key']) ? __($n->data['body_key'], $n->data['body_params'] ?? []) : ($n->data['body'] ?? '') }}</p>
                                    <p class="text-[10px] text-ttu-gray/70 mt-1.5">{{ $n->created_at->diffForHumans() }}</p>

                                    @if (($n->data['type'] ?? null) === 'doctor_message' && auth()->user()->isDoctor())
                                        <div class="notif-reply-box mt-2 flex items-center gap-2" onclick="event.stopPropagation()">
                                            <input type="text" placeholder="{{ __('common.header.reply_placeholder') }}"
                                                   class="notif-reply-input flex-1 min-w-0 rounded-lg neu-pressed bg-ttu-cream border-0 px-3 py-1.5 text-xs focus:ring-2 focus:ring-ttu-red/30 outline-none">
                                            <button type="button" onclick="notifSendReply(this, {{ $n->data['message_id'] }})"
                                                    class="shrink-0 rounded-lg bg-ttu-red text-white text-xs font-bold px-3 py-1.5">
                                                {{ __('common.header.reply') }}
                                            </button>
                                        </div>
                                        <p class="notif-reply-status hidden text-[10px] text-green-600 dark:text-green-400 mt-1.5">{{ __('common.header.reply_sent') }} ✓</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-ttu-gray text-center py-6">{{ __('common.header.no_notifications') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endauth

                {{-- اللغة + البانل --}}
                <div class="relative">
                    <button type="button" id="lang-toggle" title="{{ __('common.header.toggle_language') }}" aria-label="{{ __('common.header.toggle_language') }}"
                            class="{{ $iconBtnClass }} w-10 h-10 flex items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3c2.2 2.4 3.4 5.6 3.4 9s-1.2 6.6-3.4 9c-2.2-2.4-3.4-5.6-3.4-9s1.2-6.6 3.4-9z" />
                        </svg>
                    </button>

                    <div id="lang-panel"
                         class="hidden absolute top-full start-0 mt-3 w-48 rounded-2xl neu-raised-white p-2">
                        @foreach (config('app.supported_locales') as $code => $locale)
                            <a href="{{ route('locale.switch', $code) }}"
                               @class([
                                   'neu-icon-btn w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition text-sm font-semibold',
                                   'mt-2' => !$loop->first,
                                   'text-ttu-gray' => app()->getLocale() !== $code,
                               ])>
                                <span class="text-lg leading-none">{{ $locale['flag'] }}</span>
                                <span class="flex-1 text-start">{{ $locale['name'] }}</span>
                                @if (app()->getLocale() === $code)
                                    <svg class="w-4 h-4 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- الوضع الليلي --}}
                <button type="button" id="theme-toggle" title="{{ __('common.header.dark_mode') }}" aria-label="{{ __('common.header.dark_mode') }}"
                        class="{{ $iconBtnClass }} w-10 h-10 flex items-center justify-center rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }} dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }} hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <circle cx="12" cy="12" r="4" />
                        <path stroke-linecap="round" d="M12 3v1.5M12 19.5V21M4.6 4.6l1.06 1.06M18.34 18.34l1.06 1.06M3 12h1.5M19.5 12H21M4.6 19.4l1.06-1.06M18.34 5.66l1.06-1.06" />
                    </svg>
                </button>

            </div>

            {{-- روابط التنقل --}}
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold">
                <a href="{{ route('home') }}"
                   class="{{ $navLinkClass }} {{ $transparent && request()->routeIs('home') ? 'nav-link-active' : '' }}">
                    {{ __('common.nav.home') }}
                </a>
                @auth
                    @if (auth()->user()->isStudent() || auth()->user()->isStaff())
                        <a href="{{ route('contact') }}" class="{{ $navLinkClass }}">{{ __('common.nav.contact') }}</a>
                    @endif
                @endauth
            </nav>

        </div>

        {{-- اللوجو --}}
        <a href="{{ route('home') }}" class="flex items-center shrink-0">
            @if ($transparent)
                <img src="{{ asset('images/TTU-Clinic.png') }}" alt="{{ __('common.app_title') }}" class="h-14 sm:h-16 w-auto">
            @else
                <img src="{{ asset('images/TTU-Clinic.png') }}" alt="{{ __('common.app_title') }}" class="h-14 w-auto">
            @endif
        </a>

    </div>
</header>

@auth
<script>
    const notifMarkReadTemplate = "{{ route('notifications.read', ['notification' => '__ID__']) }}";
    const notifMarkAllUrl = "{{ route('notifications.read-all') }}";
    const notifReplyTemplate = "{{ route('messages.reply', ['message' => '__ID__']) }}";

    function notifCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function notifSendReply(button, messageId) {
        const box = button.closest('.notif-reply-box');
        const input = box.querySelector('.notif-reply-input');
        const body = input.value.trim();
        if (!body) return;

        button.disabled = true;

        fetch(notifReplyTemplate.replace('__ID__', messageId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': notifCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ body }),
        })
            .then((res) => res.json())
            .then(() => {
                box.classList.add('hidden');
                const status = box.nextElementSibling;
                if (status) status.classList.remove('hidden');
            })
            .catch(() => { button.disabled = false; });
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
            const url = item.dataset.url;
            notifMarkItemRead(item);
            if (url) {
                window.location.href = url;
            }
        }
    });
</script>
@endauth

<script>
    document.addEventListener('click', function (e) {
        const toggle = document.getElementById('lang-toggle');
        const panel = document.getElementById('lang-panel');
        if (!toggle || !panel) return;

        if (toggle.contains(e.target)) {
            panel.classList.toggle('hidden');
        } else if (!panel.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
</script>

<script>
    (function () {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;

        themeToggle.addEventListener('click', function () {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('ttu-theme', isDark ? 'dark' : 'light');
            document.dispatchEvent(new CustomEvent('ttu-theme-change', { detail: { dark: isDark } }));
        });
    })();
</script>
