@extends('layouts.main')

@section('title', __('dashboard.title'))

@section('content')

<x-app-header />

@php
    $lastVisit = $recentBookings->first();
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        {{-- ============ بطاقة الملف الشخصي ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex flex-col sm:flex-row sm:items-center gap-6">

            <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-red to-ttu-red-dark flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                    <rect width="20" height="14" x="2" y="6" rx="2" />
                </svg>
            </div>

            <div class="flex-1">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">{{ __('dashboard.staff.badge') }}</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">
                    {{ __('dashboard.greeting', ['name' => auth()->user()->name]) }} 👋
                </h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ __('dashboard.staff.identifier_label') }}: {{ auth()->user()->identifier }}</p>
            </div>

            {{-- شارات إحصائية سريعة --}}
            <div class="flex gap-3">
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">{{ __('dashboard.last_visit') }}</p>
                    <p class="text-sm font-bold text-ttu-black">
                        @if ($lastVisit)
                            {{ \Carbon\Carbon::parse($lastVisit->booking_date)->translatedFormat('d M') }}
                        @else
                            {{ __('dashboard.none') }}
                        @endif
                    </p>
                </div>
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">{{ __('dashboard.status') }}</p>
                    <p class="text-sm font-bold text-green-600 dark:text-green-400 flex items-center justify-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ __('dashboard.active') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ============ الخدمات ============ --}}
        <div class="mb-4">
            <h3 class="font-display text-lg font-bold">{{ __('dashboard.services_heading') }}</h3>
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
                    <h3 class="relative font-display text-base font-bold mb-1.5">{{ __('dashboard.booking_card.title') }}</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">{{ __('dashboard.booking_card.has_active') }}</p>
                </button>
            @else
                <a href="{{ route('booking.index') }}"
                   class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover">
                    <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                        <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <h3 class="relative font-display text-base font-bold mb-1.5">{{ __('dashboard.booking_card.title') }}</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">{{ __('dashboard.booking_card.cta') }}</p>
                </a>
            @endif

            {{-- تواصل معنا --}}
            <a href="{{ route('contact') }}"
               class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover">
                <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                    <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
                <h3 class="relative font-display text-base font-bold mb-1.5">{{ __('dashboard.contact_card.title') }}</h3>
                <p class="relative text-xs text-ttu-gray leading-relaxed">{{ __('dashboard.contact_card.description') }}</p>
            </a>

            {{-- أدويتي --}}
            <a href="{{ route('medications.mine') }}"
               class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover">
                <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                    <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <rect x="3" y="9" width="18" height="6" rx="3" />
                        <line x1="12" y1="9" x2="12" y2="15" />
                    </svg>
                </div>
                <h3 class="relative font-display text-base font-bold mb-1.5">{{ __('dashboard.medications_card.title') }}</h3>
                <p class="relative text-xs text-ttu-gray leading-relaxed">{{ __('dashboard.medications_card.description') }}</p>
            </a>

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
                    <h3 class="relative font-display text-base font-bold mb-1.5">{{ __('common.buttons.logout') }}</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">{{ __('common.buttons.logout_desc') }}</p>
                </button>
            </form>

        </div>

        {{-- ============ سجل الزيارات ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-8">
            <h3 class="font-display text-lg font-bold mb-6">{{ __('dashboard.visits.heading') }}</h3>

            @if ($recentBookings->isEmpty())
                <div class="text-center py-10">
                    <div class="w-16 h-16 rounded-full neu-pressed flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-ttu-gray" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <p class="text-sm text-ttu-gray mb-4">{{ __('dashboard.visits.empty') }}</p>
                    <a href="{{ route('booking.index') }}" class="btn-hero !py-2.5 !px-6 text-sm">{{ __('dashboard.visits.first_booking_cta') }}</a>
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
                                    <p class="text-xs text-ttu-gray mt-0.5">{{ __('dashboard.visits.hour_prefix') }} {{ sprintf('%d:%02d', $booking->booking_hour, $booking->booking_minute) }}</p>
                                </div>
                            </div>
                            <x-booking-status-badge :booking="$booking" />
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