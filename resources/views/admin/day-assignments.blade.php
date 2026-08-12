@extends('layouts.main')

@section('title', __('admin_day_assignments.title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-2">{{ __('admin_day_assignments.heading') }}</h2>
        <p class="text-sm text-ttu-gray mb-8 max-w-2xl">{{ __('admin_day_assignments.intro') }}</p>

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 dark:text-green-400 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 dark:text-red-400 text-sm px-5 py-3.5 mb-6">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @include('partials.admin-nav')

        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="space-y-3">
                @foreach ($rows as $row)
                    <div class="rounded-2xl neu-pressed px-5 py-4 flex flex-col lg:flex-row lg:items-center gap-4">

                        <p class="text-sm font-bold text-ttu-black w-32 shrink-0">{{ $row['day_name'] }}</p>

                        <div class="flex-1 flex items-center gap-3 flex-wrap">
                            @if ($row['doctor'])
                                <span class="w-9 h-9 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-bold text-ttu-red text-sm">
                                    {{ $row['doctor']->nameInitial() }}
                                </span>
                                <span class="text-sm font-bold text-ttu-black">{{ $row['doctor']->name }}</span>
                            @else
                                <span class="text-sm text-ttu-gray">{{ __('admin_day_assignments.unassigned') }}</span>
                            @endif

                            @if ($row['mismatch'])
                                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-red-50 dark:bg-red-500/15 text-red-600 dark:text-red-400">
                                    ⚠ {{ __('admin_day_assignments.mismatch_warning') }}
                                </span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.day-assignments.update') }}" class="flex items-center gap-2 shrink-0">
                            @csrf
                            <input type="hidden" name="day_of_week" value="{{ $row['day_of_week'] }}">
                            <select name="doctor_id" class="rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm outline-none">
                                @foreach ($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ $row['doctor']?->id === $doctor->id ? 'selected' : '' }}>
                                        {{ $doctor->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2.5 rounded-xl whitespace-nowrap">
                                {{ __('admin_day_assignments.reassign_button') }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
