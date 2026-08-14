@extends('layouts.main')

@section('title', __('admin_booking_history.title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-2">{{ __('admin_booking_history.heading') }}</h2>
        <p class="text-sm text-ttu-gray mb-8 max-w-2xl">{{ __('admin_booking_history.intro') }}</p>

        @include('partials.admin-nav')

        {{-- فلترة --}}
        <form method="GET" action="{{ route('admin.booking-history') }}" class="flex flex-wrap gap-3 mb-6 items-end">
            {{-- فلتر الأسبوع — أسابيع كاملة (سبت→جمعة) بدل مدى تواريخ حر --}}
            <div class="min-w-[240px]">
                <label class="block text-[11px] font-bold text-ttu-gray mb-1">{{ __('admin_booking_history.filters.week_label') }}</label>
                <select name="week" class="w-full rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm outline-none">
                    <option value="">{{ __('admin_booking_history.filters.week_all') }}</option>
                    @foreach ($weekOptions as $option)
                        <option value="{{ $option['value'] }}" @selected($selectedWeek === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[220px]">
                <label class="block text-[11px] font-bold text-ttu-gray mb-1">{{ __('admin_booking_history.filters.search_label') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('admin_booking_history.filters.search_placeholder') }}"
                       class="w-full rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
            </div>
            <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-6 py-2.5 rounded-xl">
                {{ __('admin_booking_history.filters.apply') }}
            </button>
            {{-- $hasFilters بدل array_filter: قيمة "هذا الأسبوع" هي 0 وكانت
                 array_filter ستعتبرها فارغة فيختفي زر إلغاء التصفية --}}
            @if ($hasFilters)
                <a href="{{ route('admin.booking-history') }}" class="neu-icon-btn bg-ttu-cream text-ttu-red text-sm font-bold px-6 py-2.5 rounded-xl">
                    {{ __('admin_booking_history.filters.clear') }}
                </a>
            @endif
        </form>

        {{-- القائمة --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="space-y-3">
                @forelse ($bookings as $booking)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4">
                        <div class="flex items-center gap-4">
                            <span class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-bold text-ttu-red">
                                {{ $booking->user->nameInitial() }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ttu-black">{{ $booking->user->name }}</p>
                                <p class="text-xs text-ttu-gray mt-0.5">{{ $booking->user->identifier }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed text-ttu-black">
                                {{ $booking->booking_date->translatedFormat('d F Y') }} — {{ $booking->timeLabel() }}
                            </span>

                            <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed
                                {{ $booking->user->role == 'student' ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ __('common.roles.'.$booking->user->role) }}
                            </span>

                            <x-booking-status-badge :booking="$booking" />
                        </div>
                    </div>
                @empty
                    <p class="text-center text-sm text-ttu-gray py-10">{{ __('admin_booking_history.empty') }}</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $bookings->links() }}
            </div>
        </div>

    </div>
</div>
@endsection
