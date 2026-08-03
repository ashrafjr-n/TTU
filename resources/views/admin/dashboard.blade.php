@extends('layouts.main')

@section('title', 'لوحة المدير')

@section('content')

<x-app-header />

@php
    $initial = mb_substr(auth()->user()->name, 0, 1);
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        {{-- بطاقة البروفايل --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex items-center gap-6">
            <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-black to-ttu-black flex items-center justify-center shrink-0">
                <span class="font-display text-3xl font-extrabold text-white">{{ $initial }}</span>
            </div>
            <div>
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">لوحة الإدارة</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">مرحبًا، {{ auth()->user()->name }} 👋</h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ auth()->user()->email }}</p>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">{{ session('error') }}</div>
        @endif

        @include('partials.admin-nav')

        {{-- بطاقات الإحصائيات --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_students'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">طالب مسجل</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_staff'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">موظف مسجل</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_doctors'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">دكتور</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['today_bookings'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">حجز اليوم</p>
            </div>
        </div>

    </div>
</div>
@endsection