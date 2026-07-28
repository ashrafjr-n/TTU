@extends('layouts.main')

@section('title', 'حول - عيادة TTU')

@section('content')

@include('partials.app-header')

<div class="min-h-screen bg-ttu-cream">

    {{-- ============ الهيرو ============ --}}
    <section class="relative pt-32 lg:pt-40 pb-20 lg:pb-24 px-6 text-center overflow-hidden">

        <div class="absolute inset-0 m-auto w-96 h-96 rounded-full bg-ttu-red/5 blur-3xl pointer-events-none"></div>

        <div class="relative max-w-2xl mx-auto">
            <img src="{{ asset('images/TTU-logo.png') }}"
                 alt="شعار جامعة TTU"
                 class="w-24 h-24 sm:w-28 sm:h-28 mx-auto mb-6 object-contain rounded-3xl neu-icon bg-white p-3">

            <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">من نحن</span>
            <h1 class="font-display text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold leading-[1.3] mb-4">
                عن عيادة TTU
            </h1>
            <p class="text-lg text-ttu-gray leading-relaxed max-w-xl mx-auto">
                نظام إلكتروني ذكي لحجز مواعيد العيادة الطبية الجامعية، صُمم لتسهيل الوصول
                إلى الرعاية الصحية على طلاب وموظفي الجامعة، بخطوات بسيطة وسريعة.
            </p>
        </div>
    </section>

    <div class="max-w-6xl mx-auto px-6 pb-24 space-y-10">

        {{-- ============ عن المشروع ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20 items-center">

                <div>
                    <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">مشروع تخرج</span>
                    <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-4">فكرة المشروع</h2>
                    <p class="text-base sm:text-lg text-ttu-gray leading-relaxed mb-4">
                        انطلق هذا المشروع من مشكلة يومية بسيطة: <span class="text-ttu-red font-semibold">الانتظار الطويل</span>
                        أمام عيادة الجامعة وصعوبة معرفة الأوقات المتاحة مسبقًا.
                    </p>
                    <p class="text-base sm:text-lg text-ttu-gray leading-relaxed">
                        جاء النظام كمشروع تخرج بهدف بناء منصة إلكترونية تنظّم عملية الحجز بالكامل،
                        وتوزّع الأوقات بعدالة بين الطلاب والموظفين، وتمنح الطبيب أداة سهلة
                        لإدارة جدوله اليومي.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">100%</p>
                        <p class="text-xs text-ttu-gray">إلكتروني بالكامل</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">8</p>
                        <p class="text-xs text-ttu-gray">ساعات دوام يوميًا</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">3</p>
                        <p class="text-xs text-ttu-gray">أنواع حسابات</p>
                    </div>
                    <div class="rounded-2xl neu-pressed px-5 py-6 text-center">
                        <p class="font-display text-2xl sm:text-3xl font-extrabold text-ttu-red mb-1">0.25</p>
                        <p class="text-xs text-ttu-gray">د.أ رسوم الحجز</p>
                    </div>
                </div>

            </div>
        </div>

        {{-- ============ عن العيادة ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised py-14 px-6 lg:px-12">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">العيادة</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">خدماتنا داخل الحرم الجامعي</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

                <div class="rounded-2xl neu-raised-white p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="clock" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <h3 class="font-display font-bold text-base mb-1.5">دوام يومي</h3>
                    <p class="text-sm text-ttu-gray">من الساعة 8 صباحًا حتى 4 عصرًا</p>
                </div>

                <div class="rounded-2xl neu-raised-white p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="stethoscope" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <h3 class="font-display font-bold text-base mb-1.5">كشف طبي عام</h3>
                    <p class="text-sm text-ttu-gray">لكافة طلاب وموظفي الجامعة</p>
                </div>

                <div class="rounded-2xl neu-raised-white p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="map-pin" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                    </div>
                    <h3 class="font-display font-bold text-base mb-1.5">داخل الحرم الجامعي</h3>
                    <p class="text-sm text-ttu-gray">قريبة من مبنى الطلاب الرئيسي</p>
                </div>

            </div>
        </div>

        {{-- ============ دعوة لإجراء ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised py-14 px-6 lg:px-12 text-center">
            <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-3">جاهز تحجز موعدك؟</h2>
            <p class="text-ttu-gray mb-8 max-w-md mx-auto">
                ما تضيع وقتك بالانتظار، احجز موعدك إلكترونيًا خلال ثوانٍ
            </p>
            <a href="{{ route('home') }}" class="btn-hero cursor-pointer">
                ابدأ الآن
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