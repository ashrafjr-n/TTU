@extends('layouts.main')

@section('title', __('home.title'))

@section('content')

<script>
    // الصفحة الرئيسية لازم تفتح دائمًا من الهيرو بالأعلى — لو الرابط الوارد
    // فيه #roles (رابط "تغيير النوع" بصفحة الدخول، سجل تصفح، Bookmark قديم)
    // المتصفح يقفز تلقائيًا لقسم اختيار الحساب بمجرد التحميل. نشيل الـ hash
    // قبل ما يوصلها المتصفح، ونمنع استرجاع موضع التمرير القديم عند العودة
    // بزر الرجوع — النقر اليدوي على روابط #roles بنفس الصفحة يضل يشتغل عادي.
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    if (location.hash) {
        history.replaceState(null, '', location.pathname + location.search);
    }
</script>

<x-app-header transparent />

{{-- ============ الهيرو ============ --}}
<section class="relative h-screen overflow-hidden bg-ttu-cream" dir="ltr">

    <img src="{{ asset('images/hero-background.png') }}"
         alt=""
         class="absolute inset-0 w-full h-full object-cover [object-position:center_65%]">

    <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/10"></div>

    <div class="relative h-full flex items-center justify-end">
        <div dir="{{ config('app.supported_locales')[app()->getLocale()]['dir'] }}" class="w-full lg:w-[52%] px-6 lg:px-10">

            <h1 class="font-display text-4xl sm:text-5xl lg:text-[3.4rem] font-extrabold leading-[1.25] text-white">
                {{ __('home.hero.heading') }}
            </h1>

            <p class="mt-5 text-lg text-white/90 leading-relaxed max-w-xl">
                {{ __('home.hero.subheading_1') }}
            </p>

            <p class="mt-2 text-lg text-white/90 leading-relaxed max-w-xl">
                {{ __('home.hero.subheading_2') }}
            </p>

            <div class="mt-9">
                <a href="#roles" class="btn-hero cursor-pointer">
                    {{ __('home.hero.choose_account') }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </a>
            </div>

            {{-- شريط معلومات — خلفية حليبية بدون ظل --}}
            <div class="mt-8 inline-flex items-stretch rounded-[24px] bg-ttu-cream divide-x divide-x-reverse divide-black/5 dark:divide-white/10 max-w-xl">
                <div class="flex items-center gap-3 px-5 py-4">
                    <svg class="w-5 h-5 text-ttu-red shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="leading-tight">
                        <p class="text-[10px] text-ttu-gray font-semibold">{{ __('home.hero.hours_label') }}</p>
                        <p class="text-xs font-bold text-ttu-black">{{ __('home.hero.hours_value') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 px-5 py-4">
                    <svg class="w-5 h-5 text-ttu-red shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m4-9.5c0-1.38-1.79-2.5-4-2.5s-4 1.12-4 2.5S9.79 11 12 11s4 1.12 4 2.5S14.21 16 12 16s-4-1.12-4-2.5" />
                    </svg>
                    <div class="leading-tight">
                        <p class="text-[10px] text-ttu-gray font-semibold">{{ __('home.hero.fee_label') }}</p>
                        <p class="text-xs font-bold text-ttu-black">{{ __('home.hero.fee_value') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 px-5 py-4">
                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-60"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                    </span>
                    <div class="leading-tight">
                        <p class="text-[10px] text-ttu-gray font-semibold">{{ __('home.hero.status_label') }}</p>
                        <p class="text-xs font-bold text-ttu-black">{{ __('home.hero.status_value') }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ============ اختيار نوع الحساب ============ --}}
<section id="roles" class="bg-ttu-cream py-24">

    <div class="max-w-7xl mx-auto px-6">

        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">{{ __('home.roles_section.eyebrow') }}</span>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold">{{ __('home.roles_section.heading') }}</h2>
            <p class="mt-3 text-ttu-gray">{{ __('home.roles_section.subheading') }}</p>
        </div>

        @php
            $currentRole = auth()->check() ? auth()->user()->role : null;
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

            {{-- طالب --}}
            @php $locked = $currentRole && $currentRole !== 'student'; @endphp
            <a @if (!$locked) href="{{ route('login', ['role' => 'student']) }}" @endif
               @if ($locked) aria-disabled="true" tabindex="-1" @endif
               @class([
                   'group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white',
                   'neu-card-hover cursor-pointer' => !$locked,
                   'role-locked pointer-events-none' => $locked,
               ])>

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="graduation-cap" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">{{ __('home.roles_section.student.title') }}</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    {{ __('home.roles_section.student.description') }}
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">{{ __('home.roles_section.student.login_via') }}</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">{{ __('home.roles_section.start_now') }}</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

            {{-- موظف --}}
            @php $locked = $currentRole && $currentRole !== 'staff'; @endphp
            <a @if (!$locked) href="{{ route('login', ['role' => 'staff']) }}" @endif
               @if ($locked) aria-disabled="true" tabindex="-1" @endif
               @class([
                   'group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white',
                   'neu-card-hover cursor-pointer' => !$locked,
                   'role-locked pointer-events-none' => $locked,
               ])>

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="briefcase" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">{{ __('home.roles_section.staff.title') }}</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    {{ __('home.roles_section.staff.description') }}
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">{{ __('home.roles_section.staff.login_via') }}</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">{{ __('home.roles_section.start_now') }}</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

            {{-- دكتور --}}
            @php $locked = $currentRole && $currentRole !== 'doctor'; @endphp
            <a @if (!$locked) href="{{ route('login') }}" @endif
               @if ($locked) aria-disabled="true" tabindex="-1" @endif
               @class([
                   'group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white',
                   'neu-card-hover cursor-pointer' => !$locked,
                   'role-locked pointer-events-none' => $locked,
               ])>

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="stethoscope" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">{{ __('home.roles_section.doctor.title') }}</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    {{ __('home.roles_section.doctor.description') }}
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">{{ __('home.roles_section.doctor.login_via') }}</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">{{ __('home.roles_section.login_cta') }}</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

            {{-- مدير --}}
            @php $locked = $currentRole && $currentRole !== 'admin'; @endphp
            <a @if (!$locked) href="{{ route('login') }}" @endif
               @if ($locked) aria-disabled="true" tabindex="-1" @endif
               @class([
                   'group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white',
                   'neu-card-hover cursor-pointer' => !$locked,
                   'role-locked pointer-events-none' => $locked,
               ])>

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="shield-check" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">{{ __('home.roles_section.admin.title') }}</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    {{ __('home.roles_section.admin.description') }}
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">{{ __('home.roles_section.admin.login_via') }}</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">{{ __('home.roles_section.login_cta') }}</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

        </div>

        {{-- ============ كيف تحجز موعدك؟ ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12 mt-10">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">{{ __('home.how_it_works.eyebrow') }}</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">{{ __('home.how_it_works.heading') }}</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

                <div class="relative rounded-2xl neu-pressed p-6 text-center">
                    <span class="absolute -top-3 -right-3 w-8 h-8 rounded-full neu-badge flex items-center justify-center font-display font-extrabold text-ttu-red text-xs">01</span>
                    <div class="w-12 h-12 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="user-plus" class="w-5 h-5 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <p class="text-sm font-bold text-ttu-black">{{ __('home.how_it_works.step_1') }}</p>
                </div>

                <div class="relative rounded-2xl neu-pressed p-6 text-center">
                    <span class="absolute -top-3 -right-3 w-8 h-8 rounded-full neu-badge flex items-center justify-center font-display font-extrabold text-ttu-red text-xs">02</span>
                    <div class="w-12 h-12 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-search" class="w-5 h-5 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <p class="text-sm font-bold text-ttu-black">{{ __('home.how_it_works.step_2') }}</p>
                </div>

                <div class="relative rounded-2xl neu-pressed p-6 text-center">
                    <span class="absolute -top-3 -right-3 w-8 h-8 rounded-full neu-badge flex items-center justify-center font-display font-extrabold text-ttu-red text-xs">03</span>
                    <div class="w-12 h-12 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="check-circle" class="w-5 h-5 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <p class="text-sm font-bold text-ttu-black">{{ __('home.how_it_works.step_3') }}</p>
                </div>

                <div class="relative rounded-2xl neu-pressed p-6 text-center">
                    <span class="absolute -top-3 -right-3 w-8 h-8 rounded-full neu-badge flex items-center justify-center font-display font-extrabold text-ttu-red text-xs">04</span>
                    <div class="w-12 h-12 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="footprints" class="w-5 h-5 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <p class="text-sm font-bold text-ttu-black">{{ __('home.how_it_works.step_4') }}</p>
                </div>

            </div>
        </div>

        {{-- ============ حول العيادة ============ --}}
        <div id="about" class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12 mt-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20 items-center">

                <div>
                    <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">{{ __('home.about.eyebrow') }}</span>
                    <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-4">{{ __('home.about.heading') }}</h2>
                    <p class="text-base text-ttu-gray leading-relaxed mb-4">
                        {{ __('home.about.paragraph_1') }}
                    </p>
                    <p class="text-base text-ttu-gray leading-relaxed">
                        {{ __('home.about.paragraph_2_before') }} <span class="text-ttu-red font-semibold">{{ __('home.about.paragraph_2_highlight') }}</span>
                        {{ __('home.about.paragraph_2_after') }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">100%</p>
                        <p class="text-xs text-ttu-gray">{{ __('home.about.stat_electronic') }}</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">8</p>
                        <p class="text-xs text-ttu-gray">{{ __('home.about.stat_hours') }}</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">3</p>
                        <p class="text-xs text-ttu-gray">{{ __('home.about.stat_booking_limit') }}</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">0.20</p>
                        <p class="text-xs text-ttu-gray">{{ __('home.about.stat_fee') }}</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

{{-- ============ فوتر بسيط ============ --}}
<footer class="border-t border-black/10 dark:border-white/10 bg-ttu-cream">
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ttu-gray">
        <span>{{ __('common.footer.copyright') }}</span>
        <span>{{ __('common.footer.project') }}</span>
    </div>
</footer>

{{-- زر العودة للأعلى --}}
<button id="scrollTopBtn" type="button" aria-label="{{ __('home.scroll_top') }}"
        class="btn-hero cursor-pointer fixed bottom-6 left-6 z-50 !w-12 !h-12 !p-0 !rounded-full justify-center opacity-0 pointer-events-none translate-y-4 transition-all duration-300">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
    </svg>
</button>
<style>
    html { scroll-behavior: smooth; }
</style>
<script>
    const scrollBtn = document.getElementById('scrollTopBtn');
    window.addEventListener('scroll', function () {
        if (window.scrollY > 400) {
            scrollBtn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
            scrollBtn.classList.add('opacity-100');
        } else {
            scrollBtn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
            scrollBtn.classList.remove('opacity-100');
        }
    });
    scrollBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

@endsection