@props(['transparent' => false])

@php
    $headerClass = $transparent
        ? 'absolute top-0 inset-x-0 z-50 bg-white/0'
        : 'sticky top-0 z-50 bg-ttu-cream/90 backdrop-blur border-b border-black/5 dark:border-white/10';

    $heightClass = $transparent ? 'h-24' : 'h-20';
    $iconBtnClass = $transparent ? 'glass-icon-btn' : 'neu-icon-btn bg-ttu-cream';
    $iconColorClass = $transparent ? 'text-white' : 'text-ttu-black';
    $navLinkClass = $transparent ? 'nav-link' : 'nav-link-dark';
@endphp

<header class="{{ $headerClass }}">
    <div class="max-w-7xl mx-auto px-6 {{ $heightClass }} flex items-center justify-between gap-6">

        <div class="flex items-center gap-8">

            {{-- الأيقونات --}}
            <div class="flex items-center gap-2">

                {{-- الإشعارات --}}
                @include('partials.notification-bell', [
                    'iconBtnClass' => $iconBtnClass,
                    'iconColorClass' => $iconColorClass,
                ])

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

                {{-- زر القائمة (موبايل فقط) --}}
                <div class="relative md:hidden">
                    <button type="button" id="mobile-nav-toggle" title="{{ __('common.header.menu') }}" aria-label="{{ __('common.header.menu') }}"
                            class="{{ $iconBtnClass }} w-10 h-10 flex items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>

                    <div id="mobile-nav-panel"
                         class="hidden absolute top-full start-0 mt-3 w-48 rounded-2xl neu-raised-white p-2 z-50">
                        <a href="{{ route('home') }}"
                           class="neu-icon-btn w-full flex items-center px-3.5 py-2.5 rounded-xl transition text-sm font-semibold text-ttu-black">
                            {{ __('common.nav.home') }}
                        </a>
                    </div>
                </div>

            </div>

            {{-- روابط التنقل --}}
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold">
                <a href="{{ route('home') }}"
                   class="{{ $navLinkClass }} {{ $transparent && request()->routeIs('home') ? 'nav-link-active' : '' }}">
                    {{ __('common.nav.home') }}
                </a>
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


<script>
    document.addEventListener('click', function (e) {
        const toggle = document.getElementById('mobile-nav-toggle');
        const panel = document.getElementById('mobile-nav-panel');
        if (!toggle || !panel) return;

        if (toggle.contains(e.target)) {
            panel.classList.toggle('hidden');
        } else if (!panel.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
</script>

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
