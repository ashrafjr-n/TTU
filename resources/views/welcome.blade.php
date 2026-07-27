@extends('layouts.main')

@section('title', 'عيادة TTU - الرئيسية')

@section('content')

@include('partials.navbar')

{{-- ============ الهيرو ============ --}}
<section class="relative h-screen overflow-hidden bg-ttu-cream" dir="ltr">

    <img src="{{ asset('images/hero-background.png') }}"
         alt=""
         class="absolute inset-0 w-full h-full object-cover [object-position:center_65%]">

    <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/10"></div>

    <div class="relative h-full flex items-center justify-end">
        <div dir="rtl" class="w-full lg:w-[52%] px-6 lg:px-10">

            <h1 class="font-display text-4xl sm:text-5xl lg:text-[3.4rem] font-extrabold leading-[1.25] text-white">
                رعايتك الصحية بخطوة واحدة.
            </h1>

            <p class="mt-5 text-lg text-white/90 leading-relaxed max-w-xl">
                احجز موعدك بسهولة في العيادة الطبية الجامعية.
            </p>

            <p class="mt-2 text-lg text-white/90 leading-relaxed max-w-xl">
                نظام إلكتروني ذكي لتنظيم المواعيد وتقديم خدمة أفضل للجميع.
            </p>

            <div class="mt-9">
                <a href="#roles" class="btn-hero cursor-pointer">
                    اختر نوع حسابك
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </a>
            </div>

            {{-- شريط معلومات — خلفية حليبية بدون ظل --}}
            <div class="mt-8 inline-flex items-stretch rounded-[24px] bg-ttu-cream divide-x divide-x-reverse divide-black/5 max-w-xl">
                <div class="flex items-center gap-3 px-5 py-4">
                    <svg class="w-5 h-5 text-ttu-red shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="leading-tight">
                        <p class="text-[10px] text-ttu-gray font-semibold">الدوام</p>
                        <p class="text-xs font-bold text-ttu-black">8 ص - 4 م</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 px-5 py-4">
                    <svg class="w-5 h-5 text-ttu-red shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m4-9.5c0-1.38-1.79-2.5-4-2.5s-4 1.12-4 2.5S9.79 11 12 11s4 1.12 4 2.5S14.21 16 12 16s-4-1.12-4-2.5" />
                    </svg>
                    <div class="leading-tight">
                        <p class="text-[10px] text-ttu-gray font-semibold">رسوم الحجز</p>
                        <p class="text-xs font-bold text-ttu-black">0.25 د.أ</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 px-5 py-4">
                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-60"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                    </span>
                    <div class="leading-tight">
                        <p class="text-[10px] text-ttu-gray font-semibold">الحالة</p>
                        <p class="text-xs font-bold text-ttu-black">الحجز متاح الآن</p>
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
            <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">من أنت؟</span>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold">اختر نوع الحساب للمتابعة</h2>
            <p class="mt-3 text-ttu-gray">سنوجهك مباشرة إلى الصفحة المناسبة حسب صفتك داخل الجامعة</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

            {{-- طالب --}}
            <a href="{{ route('register', ['role' => 'student']) }}"
               class="group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white neu-card-hover cursor-pointer">

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="graduation-cap" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">طالب</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    احجز موعدك وتابع حالة طلباتك أولًا بأول.
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">الدخول عبر الرقم الجامعي</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">ابدأ الآن</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

            {{-- موظف --}}
            <a href="{{ route('register', ['role' => 'staff']) }}"
               class="group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white neu-card-hover cursor-pointer">

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="briefcase" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">موظف</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    احجز موعدك ضمن الحصة المخصصة للعاملين بالجامعة.
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">الدخول عبر الرقم الوظيفي</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">ابدأ الآن</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

            {{-- دكتور --}}
            <a href="{{ route('login') }}"
               class="group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white neu-card-hover cursor-pointer">

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="stethoscope" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">دكتور</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    إدارة جدول العيادة، ومراجعة طلبات الحجز واتخاذ القرار بشأنها.
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">الدخول عبر البريد الجامعي</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">تسجيل الدخول</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

            {{-- مدير --}}
            <a href="{{ route('login') }}"
               class="group relative flex flex-col overflow-hidden p-8 pt-10 rounded-[2.5rem] neu-raised-white neu-card-hover cursor-pointer">

                <div class="relative w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mb-6 group-hover:bg-ttu-red transition-colors duration-300">
                    <i data-lucide="shield-check" class="neu-wiggle w-7 h-7 text-ttu-red group-hover:text-white transition-colors duration-300" stroke-width="1.6"></i>
                </div>

                <h3 class="relative font-display text-xl font-bold mb-1.5">مدير</h3>
                <p class="relative text-sm text-ttu-gray leading-relaxed mb-2">
                    إشراف كامل على النظام وإدارة الحسابات والصلاحيات.
                </p>
                <p class="relative text-xs text-ttu-red font-semibold mb-6">الدخول عبر البريد الإداري</p>

                <span class="relative mt-auto pt-5 flex items-center justify-between">
                    <span class="text-sm font-semibold">تسجيل الدخول</span>
                    <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream group-hover:bg-ttu-red flex items-center justify-center transition-colors duration-300">
                        <svg class="w-4 h-4 text-ttu-red group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                </span>
            </a>

        </div>
    </div>
</section>

{{-- ============ لماذا نظامنا؟ ============ --}}
<section class="relative bg-ttu-cream">

    <div class="max-w-6xl mx-auto px-6 pt-24 pb-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">لماذا نظامنا؟</span>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold">حلول ذكية لتنظيم مواعيد العيادة</h2>
        </div>
    </div>

    <div class="relative pb-24">

        <div class="max-w-6xl mx-auto px-6 space-y-10">

            {{-- 1: الانتظار الطويل --}}
            <div class="relative rounded-[2.5rem] neu-raised py-14 px-6 lg:px-12">
                <span class="hidden lg:flex absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 rounded-full neu-badge items-center justify-center font-display font-extrabold text-ttu-red text-sm z-10">01</span>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-28 items-center">
                    <div>
                        <span class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-full neu-badge text-ttu-red font-display font-extrabold text-xs mb-4">01</span>
                        <h3 class="font-display text-3xl font-bold mb-4">الانتظار الطويل</h3>
                        <p class="text-lg text-ttu-gray leading-relaxed max-w-md">
                            بدلاً من <span class="text-ttu-red font-semibold">الانتظار أمام العيادة</span>،
                            يمكنك معرفة المواعيد المتاحة وحجزها إلكترونياً.
                        </p>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-0 m-auto w-56 h-56 rounded-full bg-ttu-red/10 blur-3xl pointer-events-none"></div>
                        <div id="why-lottie-1" class="relative w-full max-w-sm mx-auto aspect-square"></div>
                    </div>
                </div>
            </div>

            {{-- 2: إدارة دقيقة للمواعيد --}}
            <div class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12">
                <span class="hidden lg:flex absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 rounded-full neu-badge items-center justify-center font-display font-extrabold text-ttu-red text-sm z-10">02</span>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-28 items-center">
                    <div class="relative order-2 lg:order-1">
                        <div class="absolute inset-0 m-auto w-56 h-56 rounded-full bg-ttu-red/10 blur-3xl pointer-events-none"></div>
                        <div id="why-lottie-2" class="relative w-full max-w-sm mx-auto aspect-square"></div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <span class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-full neu-badge text-ttu-red font-display font-extrabold text-xs mb-4">02</span>
                        <h3 class="font-display text-3xl font-bold mb-4">إدارة دقيقة للمواعيد</h3>
                        <p class="text-lg text-ttu-gray leading-relaxed max-w-md">
                            يعرض النظام <span class="text-ttu-red font-semibold">جميع الفترات المتاحة</span>
                            ويمنع الحجز المكرر تلقائياً.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 3: توزيع عادل --}}
            <div class="relative rounded-[2.5rem] neu-raised py-14 px-6 lg:px-12">
                <span class="hidden lg:flex absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 rounded-full neu-badge items-center justify-center font-display font-extrabold text-ttu-red text-sm z-10">03</span>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-28 items-center">
                    <div>
                        <span class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-full neu-badge text-ttu-red font-display font-extrabold text-xs mb-4">03</span>
                        <h3 class="font-display text-3xl font-bold mb-4">توزيع عادل</h3>

                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full neu-pill px-4 py-2 text-sm font-bold text-ttu-red">
                                9 طلاب
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full neu-pill px-4 py-2 text-sm font-bold text-ttu-red">
                                3 موظفين
                            </span>
                        </div>

                        <p class="text-lg text-ttu-gray leading-relaxed max-w-md">
                            مع إمكانية <span class="text-ttu-red font-semibold">إرسال طلب للطبيب</span>
                            عند اكتمال حصة الطلاب.
                        </p>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-0 m-auto w-56 h-56 rounded-full bg-ttu-red/10 blur-3xl pointer-events-none"></div>
                        <div id="why-lottie-3" class="relative w-full max-w-sm mx-auto aspect-square"></div>
                    </div>
                </div>
            </div>

            {{-- 4: لوحة تحكم للطبيب --}}
            <div class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12">
                <span class="hidden lg:flex absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 rounded-full neu-badge items-center justify-center font-display font-extrabold text-ttu-red text-sm z-10">04</span>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-28 items-center">
                    <div class="relative order-2 lg:order-1">
                        <div class="absolute inset-0 m-auto w-56 h-56 rounded-full bg-ttu-red/10 blur-3xl pointer-events-none"></div>
                        <div id="why-lottie-4" class="relative w-full max-w-sm mx-auto aspect-square"></div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <span class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-full neu-badge text-ttu-red font-display font-extrabold text-xs mb-4">04</span>
                        <h3 class="font-display text-3xl font-bold mb-4">لوحة تحكم للطبيب</h3>
                        <p class="text-lg text-ttu-gray leading-relaxed max-w-md">
                            <span class="text-ttu-red font-semibold">إدارة الحجوزات</span>، وقبول أو رفض الطلبات،
                            ومتابعة جدول اليوم بسهولة.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.lottie) return;
        const animations = [
            { id: 'why-lottie-1', file: 'waiting.json' },
            { id: 'why-lottie-2', file: 'r.json' },
            { id: 'why-lottie-3', file: 'health.json' },
            { id: 'why-lottie-4', file: 'doctor.json' },
        ];
        animations.forEach(function (item) {
            const container = document.getElementById(item.id);
            if (!container) return;
            lottie.loadAnimation({
                container: container, renderer: 'svg', loop: true, autoplay: true,
                path: '/animations/' + item.file,
            });
        });
    });
</script>

{{-- ============ فوتر بسيط ============ --}}
<footer class="border-t border-black/10 bg-ttu-cream">
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ttu-gray">
        <span>© 2026 عيادة TTU. جميع الحقوق محفوظة.</span>
        <span>مشروع تخرج — جامعة TTU</span>
    </div>
</footer>

{{-- زر العودة للأعلى --}}
<button id="scrollTopBtn" type="button" aria-label="العودة للأعلى"
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