@extends('layouts.main')

@section('title', __('about.title'))

@section('content')

@include('partials.auth-header')

<div class="min-h-screen bg-ttu-cream">

    {{-- ============ الهيرو ============ --}}
    <section class="relative pt-32 lg:pt-40 pb-20 lg:pb-24 px-6 text-center overflow-hidden">

        <div class="absolute inset-0 m-auto w-96 h-96 rounded-full bg-ttu-red/5 blur-3xl pointer-events-none"></div>

        <div class="relative max-w-2xl mx-auto">
            <img src="{{ asset('images/TTU-logo.png') }}"
                 alt="{{ __('about.logo_alt') }}"
                 class="w-24 h-24 sm:w-28 sm:h-28 mx-auto mb-6 object-contain rounded-3xl neu-icon bg-white p-3">

            <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">{{ __('about.hero.eyebrow') }}</span>
            <h1 class="font-display text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold leading-[1.3] mb-4">
                {{ __('about.hero.heading') }}
            </h1>
            <p class="text-lg text-ttu-gray leading-relaxed max-w-xl mx-auto">
                {{ __('home.about.paragraph_1') }}
            </p>
        </div>
    </section>

    <div class="max-w-6xl mx-auto px-6 pb-24 space-y-10">

        {{-- ============ عن المشروع ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20 items-center">

                <div>
                    <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">{{ __('about.project.eyebrow') }}</span>
                    <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-4">{{ __('about.project.heading') }}</h2>
                    <p class="text-base sm:text-lg text-ttu-gray leading-relaxed mb-4">
                        {{ __('about.project.paragraph_1_before') }} <span class="text-ttu-red font-semibold">{{ __('about.project.paragraph_1_highlight') }}</span>
                        {{ __('about.project.paragraph_1_after') }}
                    </p>
                    <p class="text-base sm:text-lg text-ttu-gray leading-relaxed">
                        {{ __('about.project.paragraph_2') }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">100%</p>
                        <p class="text-xs text-ttu-gray">{{ __('about.project.stat_electronic') }}</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">8</p>
                        <p class="text-xs text-ttu-gray">{{ __('about.project.stat_hours') }}</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">3</p>
                        <p class="text-xs text-ttu-gray">{{ __('about.project.stat_account_types') }}</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">0.20</p>
                        <p class="text-xs text-ttu-gray">{{ __('about.project.stat_fee') }}</p>
                    </div>
                </div>

            </div>
        </div>

        {{-- ============ عن العيادة ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised py-14 px-6 lg:px-12">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">{{ __('about.services.eyebrow') }}</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">{{ __('about.services.heading') }}</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

                <div class="rounded-2xl neu-raised-white p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="clock" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <h3 class="font-display font-bold text-base mb-1.5">{{ __('about.services.hours_title') }}</h3>
                    <p class="text-sm text-ttu-gray">{{ __('about.services.hours_desc') }}</p>
                </div>

                <div class="rounded-2xl neu-raised-white p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="stethoscope" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <h3 class="font-display font-bold text-base mb-1.5">{{ __('about.services.checkup_title') }}</h3>
                    <p class="text-sm text-ttu-gray">{{ __('about.services.checkup_desc') }}</p>
                </div>

                <div class="rounded-2xl neu-raised-white p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="map-pin" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <h3 class="font-display font-bold text-base mb-1.5">{{ __('about.services.location_title') }}</h3>
                    <p class="text-sm text-ttu-gray">{{ __('about.services.location_desc') }}</p>
                </div>

            </div>
        </div>

        {{-- ============ دعوة لإجراء ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised py-14 px-6 lg:px-12 text-center">
            <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-3">{{ __('about.cta.heading') }}</h2>
            <p class="text-ttu-gray mb-8 max-w-md mx-auto">
                {{ __('about.cta.subheading') }}
            </p>
            <a href="{{ route('home') }}" class="btn-hero cursor-pointer">
                {{ __('about.cta.button') }}
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5L3.75 12l7.5 7.5M20.25 12H4.5" />
                </svg>
            </a>
        </div>

    </div>

</div>

{{-- ============ فوتر بسيط ============ --}}
<footer class="border-t border-black/10 bg-ttu-cream">
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ttu-gray">
        <span>{{ __('common.footer.copyright') }}</span>
        <span>{{ __('common.footer.project') }}</span>
    </div>
</footer>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

@endsection