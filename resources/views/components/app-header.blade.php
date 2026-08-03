@props(['transparent' => false])

@php
    $headerClass = $transparent
        ? 'absolute top-0 inset-x-0 z-50 bg-white/0'
        : 'sticky top-0 z-50 bg-ttu-cream/90 backdrop-blur border-b border-black/5';

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
                <button type="button" title="الإشعارات" aria-label="الإشعارات"
                        class="{{ $iconBtnClass }} relative w-10 h-10 flex items-center justify-center rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <span class="absolute -top-1 -left-1 w-4 h-4 rounded-full bg-ttu-red text-white text-[10px] font-bold flex items-center justify-center">
                        0
                    </span>
                </button>

                {{-- اللغة + البانل --}}
                <div class="relative">
                    <button type="button" id="lang-toggle" title="تبديل اللغة" aria-label="تبديل اللغة"
                            class="{{ $iconBtnClass }} w-10 h-10 flex items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3c2.2 2.4 3.4 5.6 3.4 9s-1.2 6.6-3.4 9c-2.2-2.4-3.4-5.6-3.4-9s1.2-6.6 3.4-9z" />
                        </svg>
                    </button>

                    <div id="lang-panel"
                         class="hidden absolute top-full start-0 mt-3 w-48 rounded-2xl neu-raised-white p-2">
                        <button type="button" class="neu-icon-btn w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition text-sm font-semibold">
                            <span class="text-lg leading-none">🇯🇴</span>
                            <span class="flex-1 text-right">العربية</span>
                            <svg class="w-4 h-4 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </button>
                        <button type="button" class="neu-icon-btn w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl mt-2 transition text-sm font-semibold text-ttu-gray">
                            <span class="text-lg leading-none">🇬🇧</span>
                            <span class="flex-1 text-right">English</span>
                        </button>
                    </div>
                </div>

                {{-- الوضع الليلي --}}
                <button type="button" title="الوضع الليلي" aria-label="الوضع الليلي"
                        class="{{ $iconBtnClass }} w-10 h-10 flex items-center justify-center rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconColorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
                    </svg>
                </button>

            </div>

            {{-- روابط التنقل --}}
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold">
                <a href="{{ route('home') }}"
                   class="{{ $navLinkClass }} {{ $transparent && request()->routeIs('home') ? 'nav-link-active' : '' }}">
                    الرئيسية
                </a>
                <a href="{{ route('about') }}" class="{{ $navLinkClass }}">حول</a>
                @auth
                    <a href="#" class="{{ $navLinkClass }}">أدويتي</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="{{ $navLinkClass }}">تسجيل الخروج</button>
                    </form>
                @endauth
            </nav>

        </div>

        {{-- اللوجو --}}
        <a href="{{ route('home') }}" class="flex items-center shrink-0">
            @if ($transparent)
                <span class="logo-badge rounded-2xl px-4 py-2 flex items-center">
                    <img src="{{ asset('images/TTU-Clinic.png') }}" alt="عيادة TTU" class="h-14 sm:h-16 w-auto">
                </span>
            @else
                <img src="{{ asset('images/TTU-Clinic.png') }}" alt="عيادة TTU" class="h-14 w-auto">
            @endif
        </a>

    </div>
</header>

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
