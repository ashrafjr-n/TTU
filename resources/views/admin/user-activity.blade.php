@extends('layouts.main')

@section('title', 'سجل نشاط '.$targetUser->name)

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <div class="flex items-center justify-between gap-4 mb-8">
            <div>
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">سجل نشاط</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">{{ $targetUser->name }}</h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ $targetUser->email }} · {{ $targetUser->identifier }}</p>
            </div>

            <a href="{{ route('admin.users') }}" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2.5 rounded-xl shrink-0">
                رجوع لإدارة المستخدمين
            </a>
        </div>

        @include('admin.partials.activity-log-list', ['logs' => $logs, 'showActor' => false])

    </div>
</div>
@endsection
