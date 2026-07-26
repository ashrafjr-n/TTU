@extends('layouts.main')

@section('title', 'لوحة تحكم الدكتور')

@section('content')

@include('partials.app-header')

@php
    $initial = mb_substr(auth()->user()->name, 0, 1);
    $todayCount = $bookings->count();
    $pendingCount = $pendingRequests->count();
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        {{-- ============ بطاقة الملف الشخصي ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex flex-col sm:flex-row sm:items-center gap-6">

            <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-black to-ttu-black flex items-center justify-center shrink-0">
                <span class="font-display text-3xl font-extrabold text-white">{{ $initial }}</span>
            </div>

            <div class="flex-1">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">لوحة الدكتور</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">
                    مرحبًا، {{ auth()->user()->name }} 👋
                </h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ auth()->user()->email }}</p>
            </div>

            {{-- شارات إحصائية سريعة --}}
            <div class="flex gap-3">
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">حجوزات اليوم</p>
                    <p class="text-sm font-bold text-ttu-black">{{ $todayCount }}</p>
                </div>
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">طلبات معلقة</p>
                    <p class="text-sm font-bold {{ $pendingCount > 0 ? 'text-ttu-red' : 'text-ttu-black' }}">
                        {{ $pendingCount }}
                    </p>
                </div>
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

        {{-- ============ طلبات الحجز المعلقة ============ --}}
        @if ($pendingRequests->count() > 0)
        <div class="mb-10">
            <h3 class="font-display text-lg font-bold mb-4 flex items-center gap-2">
                طلبات بانتظار الرد
                <span class="rounded-full neu-pressed text-ttu-red text-xs font-bold px-3 py-1">{{ $pendingRequests->count() }}</span>
            </h3>

            <div class="space-y-3">
                @foreach ($pendingRequests as $req)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-[1.75rem] neu-raised-white px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-ttu-black">
                                {{ $req->user->name }}
                                <span class="text-xs text-ttu-gray font-normal">
                                    ({{ $req->user->role === 'student' ? 'طالب' : 'موظف' }} - {{ $req->user->identifier }})
                                </span>
                            </p>
                            <p class="text-xs text-ttu-gray mt-1">
                                {{ $req->booking_date->format('Y-m-d') }} &nbsp;·&nbsp; الساعة {{ $req->booking_hour }}:00
                            </p>
                            <p class="text-xs text-ttu-red mt-1">
                                ⏱ ينتهي تلقائيًا الساعة {{ $req->expires_at->format('H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <form method="POST" action="{{ route('doctor.requests.approve', $req) }}">
                            @csrf
                            <button type="submit" class="neu-icon-btn bg-ttu-cream text-green-600 text-sm font-bold px-5 py-2.5 rounded-xl hover:!bg-green-600 hover:!text-white transition-colors">
                                قبول
                            </button>
                        </form>
                        <form method="POST" action="{{ route('doctor.requests.reject', $req) }}">
                            @csrf
                            <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-red text-sm font-bold px-5 py-2.5 rounded-xl hover:!bg-ttu-red hover:!text-white transition-colors">
                                رفض
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ============ جدول الحجوزات ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-8">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h3 class="font-display text-lg font-bold">جدول الحجوزات</h3>

                <form method="GET" action="{{ route('dashboard.doctor') }}" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}"
                           class="rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                    <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2 rounded-xl">
                        عرض
                    </button>
                </form>
            </div>

            <div class="space-y-3">
                @forelse ($bookings as $b)
                    <div class="flex items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4">
                        <div class="flex items-center gap-4">
                            <span class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-extrabold text-sm text-ttu-red">
                                {{ $b->booking_hour }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ttu-black">{{ $b->user->name }}</p>
                                <p class="text-xs text-ttu-gray mt-0.5">{{ $b->user->identifier }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed {{ $b->user->role == 'student' ? 'text-blue-600' : 'text-green-600' }}">
                                {{ $b->user->role == 'student' ? 'طالب' : 'موظف' }}
                            </span>

                            <form method="POST" action="{{ route('doctor.bookings.cancel', $b) }}"
                                  onsubmit="return confirm('متأكد من إلغاء هذا الحجز؟');">
                                @csrf
                                <button type="submit" class="neu-icon-btn w-9 h-9 rounded-full bg-ttu-cream text-ttu-red flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <div class="w-16 h-16 rounded-full neu-pressed flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-ttu-gray" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <p class="text-sm text-ttu-gray">لا يوجد حجوزات بهذا التاريخ</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection