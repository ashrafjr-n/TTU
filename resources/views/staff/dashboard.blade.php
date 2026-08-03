@extends('layouts.main')

@section('title', 'الرئيسية')

@section('content')

<x-app-header />

@php
    $initial = mb_substr(auth()->user()->name, 0, 1);
    $lastVisit = $recentBookings->first();
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        {{-- ============ بطاقة الملف الشخصي ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex flex-col sm:flex-row sm:items-center gap-6">

            <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-red to-ttu-red-dark flex items-center justify-center shrink-0">
                <span class="font-display text-3xl font-extrabold text-white">{{ $initial }}</span>
            </div>

            <div class="flex-1">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">لوحة الموظف</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">
                    مرحبًا، {{ auth()->user()->name }} 👋
                </h2>
                <p class="mt-1 text-sm text-ttu-gray">الرقم الوظيفي: {{ auth()->user()->identifier }}</p>
            </div>

            {{-- شارات إحصائية سريعة --}}
            <div class="flex gap-3">
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">آخر زيارة</p>
                    <p class="text-sm font-bold text-ttu-black">
                        @if ($lastVisit)
                            {{ \Carbon\Carbon::parse($lastVisit->booking_date)->translatedFormat('d M') }}
                        @else
                            لا توجد
                        @endif
                    </p>
                </div>
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">الحالة</p>
                    <p class="text-sm font-bold text-green-600 flex items-center justify-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span> نشط
                    </p>
                </div>
            </div>
        </div>

        {{-- ============ الخدمات ============ --}}
        <div class="mb-4">
            <h3 class="font-display text-lg font-bold">شو بدك تعمل اليوم؟</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-14">

            {{-- حجز موعد — إن وُجد حجز فعّال، الزر يفتح مودال التحذير في مكانه بدل الانتقال لصفحة الحجز --}}
            @if ($activeBooking)
                <button type="button" onclick="openActiveBookingModal()"
                        class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover text-right">
                    <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                        <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <h3 class="relative font-display text-base font-bold mb-1.5">حجز موعد</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">لديك حجز حاليًا — اضغط للتفاصيل</p>
                </button>
            @else
                <a href="{{ route('booking.index') }}"
                   class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover">
                    <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                        <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <h3 class="relative font-display text-base font-bold mb-1.5">حجز موعد</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">احجز وقتك خلال ثوانٍ</p>
                </a>
            @endif

            {{-- تواصل معنا --}}
            <a href="#"
               class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover">
                <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                    <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
                <h3 class="relative font-display text-base font-bold mb-1.5">تواصل معنا</h3>
                <p class="relative text-xs text-ttu-gray leading-relaxed">تواصل مع العيادة</p>
            </a>

            {{-- أدويتي — ميزة مستقبلية --}}
            <div class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white opacity-70">
                <span class="absolute top-4 left-4 text-[10px] font-bold text-ttu-red rounded-full neu-pressed px-2.5 py-1">قريبًا</span>
                <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <rect x="3" y="9" width="18" height="6" rx="3" />
                        <line x1="12" y1="9" x2="12" y2="15" />
                    </svg>
                </div>
                <h3 class="relative font-display text-base font-bold mb-1.5">أدويتي</h3>
                <p class="relative text-xs text-ttu-gray leading-relaxed">تقارير وأدوية وصفها الطبيب</p>
            </div>

            {{-- تسجيل الخروج --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover w-full text-right">
                    <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                        <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </div>
                    <h3 class="relative font-display text-base font-bold mb-1.5">تسجيل الخروج</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">الخروج من حسابك بأمان</p>
                </button>
            </form>

        </div>

        {{-- ============ سجل الزيارات ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-8">
            <h3 class="font-display text-lg font-bold mb-6">سجل زياراتك</h3>

            @if ($recentBookings->isEmpty())
                <div class="text-center py-10">
                    <div class="w-16 h-16 rounded-full neu-pressed flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-ttu-gray" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <p class="text-sm text-ttu-gray mb-4">لسا ما عندك أي زيارة مسجلة</p>
                    <a href="{{ route('booking.index') }}" class="btn-hero !py-2.5 !px-6 text-sm">احجز موعدك الأول</a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($recentBookings as $booking)
                        <div class="flex items-center justify-between rounded-2xl neu-pressed px-5 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-ttu-black">
                                        {{ \Carbon\Carbon::parse($booking->booking_date)->translatedFormat('d F Y') }}
                                    </p>
                                    <p class="text-xs text-ttu-gray mt-0.5">الساعة {{ sprintf('%d:%02d', $booking->booking_hour, $booking->booking_minute) }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-green-600 bg-green-50 rounded-full px-3 py-1.5">مؤكد</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

@if ($activeBooking)
    @include('booking.partials.active-booking-modal', ['activeBooking' => $activeBooking, 'autoOpen' => false])
@endif

@endsection