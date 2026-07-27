@extends('layouts.main')

@section('title', 'حجز موعد')

@section('content')

@include('partials.app-header')

@php
    $roleLabel = auth()->user()->isStudent() ? 'الطالب' : 'الموظف';
    $dashboardRoute = auth()->user()->isStudent() ? route('dashboard.student') : route('dashboard.staff');
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-4xl mx-auto px-6 py-16 lg:py-20">

        {{-- ============ رأس الصفحة ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div>
                <a href="{{ $dashboardRoute }}" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors mb-3 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    رجوع للرئيسية
                </a>
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">حجز {{ $roleLabel }}</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">احجز موعدك</h2>
            </div>

            <div class="rounded-2xl neu-pressed px-5 py-4 text-center">
                <p class="text-[11px] text-ttu-gray mb-1">رسوم الحجز</p>
                <p class="text-lg font-extrabold text-ttu-red">0.25 د.أ</p>
            </div>
        </div>

        {{-- رسائل النجاح/الخطأ --}}
        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">
                {{ session('error') }}
            </div>
        @endif

        {{-- ============ مفتاح الألوان ============ --}}
        <div class="flex flex-wrap items-center gap-4 mb-6 px-2">
            <span class="flex items-center gap-2 text-xs text-ttu-gray">
                <span class="w-3 h-3 rounded-full bg-green-500"></span> متاح
            </span>
            <span class="flex items-center gap-2 text-xs text-ttu-gray">
                <span class="w-3 h-3 rounded-full bg-ttu-red"></span> محجوز بالكامل
            </span>
            <span class="flex items-center gap-2 text-xs text-ttu-gray">
                <span class="w-3 h-3 rounded-full bg-ttu-yellow"></span> طلب موافقة
            </span>
            <span class="flex items-center gap-2 text-xs text-ttu-gray">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span> حجزك أنت
            </span>
        </div>

        {{-- ============ خانات الأوقات ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="space-y-3">
                @foreach ($slots as $slot)
                    @php
                        $status = $slot['already_booked']
                            ? 'mine'
                            : ($slot['pending_request']
                                ? 'pending'
                                : ($slot['can_book_directly']
                                    ? 'available'
                                    : ($slot['can_request'] ? 'request' : 'full')));
                    @endphp

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4">

                        <div class="flex items-center gap-4">
                            <span @class([
                                'w-14 h-14 rounded-2xl neu-icon flex items-center justify-center shrink-0 font-display font-extrabold text-sm',
                                'bg-blue-500 text-white' => $status === 'mine',
                                'bg-ttu-yellow text-white' => $status === 'pending',
                                'bg-green-500 text-white' => $status === 'available',
                                'bg-ttu-yellow/90 text-white' => $status === 'request',
                                'bg-ttu-red text-white' => $status === 'full',
                            ])>
                                {{ $slot['hour'] }}:00
                            </span>

                            <div>
                                <p class="text-sm font-bold text-ttu-black">{{ $slot['time_label'] }}</p>
                                <p class="text-xs text-ttu-gray mt-1">
                                    طلاب: {{ $slot['student_booked'] }}/{{ $slot['student_capacity'] }}
                                    &nbsp;·&nbsp;
                                    موظفين: {{ $slot['staff_booked'] }}/{{ $slot['staff_capacity'] }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if ($status === 'mine')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 text-blue-600 text-xs font-bold px-4 py-2.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    لديك حجز
                                </span>

                            @elseif ($status === 'pending')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-600 text-xs font-bold px-4 py-2.5">
                                    ⏱ قيد المراجعة
                                </span>

                            @elseif ($status === 'available')
                                <form method="POST" action="{{ route('booking.store') }}">
                                    @csrf
                                    <input type="hidden" name="hour" value="{{ $slot['hour'] }}">
                                    <button type="submit" class="neu-icon-btn bg-ttu-cream text-green-600 text-sm font-bold px-6 py-2.5 rounded-xl hover:!bg-green-600 hover:!text-white transition-colors">
                                        احجز الآن
                                    </button>
                                </form>

                            @elseif ($status === 'request')
                                <form method="POST" action="{{ route('booking.request') }}">
                                    @csrf
                                    <input type="hidden" name="hour" value="{{ $slot['hour'] }}">
                                    <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-yellow text-sm font-bold px-6 py-2.5 rounded-xl hover:!bg-ttu-yellow hover:!text-white transition-colors">
                                        إرسال طلب
                                    </button>
                                </form>

                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-ttu-cream text-ttu-gray text-xs font-bold px-4 py-2.5">
                                    محجوز بالكامل
                                </span>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

@endsection