@extends('layouts.main')

@section('title', 'تواصل معنا - عيادة TTU')

@section('content')

@include('partials.auth-header')

<div class="min-h-screen bg-ttu-cream">

    {{-- ============ الهيرو ============ --}}
    <section class="relative pt-32 lg:pt-40 pb-20 lg:pb-24 px-6 text-center overflow-hidden">

        <div class="absolute inset-0 m-auto w-96 h-96 rounded-full bg-ttu-red/5 blur-3xl pointer-events-none"></div>

        <div class="relative max-w-2xl mx-auto">
            <div class="w-20 h-20 rounded-3xl neu-icon bg-white p-3 flex items-center justify-center mx-auto mb-6">
                <i data-lucide="mail" class="w-8 h-8 text-ttu-red" stroke-width="1.6"></i>
            </div>

            <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">تواصل معنا</span>
            <h1 class="font-display text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold leading-[1.3] mb-4">
                نسعد بتواصلك معنا
            </h1>
            <p class="text-lg text-ttu-gray leading-relaxed max-w-xl mx-auto">
                هذه الصفحة قيد الإعداد حاليًا. قريبًا ستجد هنا كل طرق التواصل مع عيادة TTU.
            </p>
        </div>
    </section>

    <div class="max-w-6xl mx-auto px-6 pb-24">

        <div class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12 text-center">
            <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-5">
                <i data-lucide="hammer" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
            </div>
            <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-3">قريبًا</h2>
            <p class="text-ttu-gray max-w-md mx-auto mb-8">
                نعمل حاليًا على تصميم هذه الصفحة. بإمكانك بالعودة للرئيسية بالوقت الحالي.
            </p>
            <a href="{{ route('home') }}" class="btn-hero cursor-pointer">
                رجوع للرئيسية
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
        <span>© 2026 عيادة TTU. جميع الحقوق محفوظة.</span>
        <span>مشروع تخرج — جامعة TTU</span>
    </div>
</footer>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

@endsection
