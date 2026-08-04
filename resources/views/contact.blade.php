@extends('layouts.main')

@section('title', 'تواصل معنا - عيادة TTU')

@section('content')

@include('partials.auth-header')

<div class="min-h-screen bg-ttu-cream">

    <div class="max-w-6xl mx-auto px-6 pt-8 flex justify-end">
        <a href="{{ route('dashboard') }}"
           class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2.5 rounded-xl shrink-0">
            رجوع للوحة
        </a>
    </div>

    {{-- ============ الهيرو ============ --}}
    <section class="relative pt-32 lg:pt-40 pb-16 px-6 text-center overflow-hidden">

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
                لأي استفسار أو ملاحظة، تقدر تراسل الدكتور مباشرة من الفورم وراح يوصلك الرد على نفس جرس الإشعارات.
            </p>
        </div>
    </section>

    <div class="max-w-6xl mx-auto px-6 pb-24 space-y-10">

        {{-- ============ بيانات التواصل ============ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <div class="rounded-2xl neu-raised-white p-6 text-center">
                <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="phone" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                </div>
                <h3 class="font-display font-bold text-base mb-1.5">الهاتف</h3>
                <p class="text-sm text-ttu-gray" dir="ltr">XXX-XXXXXXX</p>
                <p class="text-[11px] text-ttu-gray/70 mt-1">(رقم تجريبي مؤقت)</p>
            </div>

            <div class="rounded-2xl neu-raised-white p-6 text-center">
                <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="mail" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                </div>
                <h3 class="font-display font-bold text-base mb-1.5">البريد الإلكتروني</h3>
                <p class="text-sm text-ttu-gray" dir="ltr">clinic@xxx.edu.jo</p>
                <p class="text-[11px] text-ttu-gray/70 mt-1">(بريد تجريبي مؤقت)</p>
            </div>

            <div class="rounded-2xl neu-raised-white p-6 text-center">
                <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="clock" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                </div>
                <h3 class="font-display font-bold text-base mb-1.5">دوام العيادة</h3>
                <p class="text-sm text-ttu-gray">من الساعة 8 صباحًا حتى 4 عصرًا</p>
            </div>

            <div class="rounded-2xl neu-raised-white p-6 text-center">
                <div class="w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="map-pin" class="w-6 h-6 text-ttu-red" stroke-width="1.6"></i>
                </div>
                <h3 class="font-display font-bold text-base mb-1.5">الموقع</h3>
                <p class="text-sm text-ttu-gray">مبنى العيادة الطبية — الطابق الأرضي، قرب مبنى الطلاب الرئيسي</p>
            </div>

        </div>

        {{-- ============ فورم التواصل ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white py-14 px-6 lg:px-12">
            <div class="text-center max-w-xl mx-auto mb-10">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-3">راسلنا</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">أرسل رسالتك</h2>
            </div>

            @if (session('success'))
                <div class="max-w-xl mx-auto rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="max-w-xl mx-auto rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="max-w-xl mx-auto space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">الاسم</label>
                    <input type="text" value="{{ auth()->user()->name }}" disabled
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 text-ttu-gray outline-none cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">الدكتور</label>
                    <select name="doctor_id" required
                            class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                        <option value="" disabled {{ old('doctor_id') ? '' : 'selected' }}>اختر الدكتور</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">رسالتك</label>
                    <textarea name="message" rows="5" required
                              class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none resize-none">{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="w-full btn-hero justify-center">
                    إرسال الرسالة
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12m0 0l-5.25-5.25M18 12l-5.25 5.25" />
                    </svg>
                </button>
            </form>
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
