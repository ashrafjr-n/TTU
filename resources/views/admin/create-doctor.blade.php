@extends('layouts.main')

@section('title', __('admin_doctor_form.create.page_title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-8">{{ __('admin_doctor_form.create.heading') }}</h2>

        @include('partials.admin-nav')

        @php
            $dayLabels = __('common.days');
            $selectedDays = collect(old('working_days', []))->map(fn ($d) => (int) $d)->all();
        @endphp

        <div class="max-w-md rounded-[2.5rem] neu-raised-white p-8">

            @if ($errors->any())
                <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-4">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.doctors.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('admin_doctor_form.name_label') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('admin_doctor_form.email_label') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="doctor-4@ttu.edu.jo"
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('admin_doctor_form.password_label') }}</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('admin_doctor_form.password_confirmation_label') }}</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('admin_doctor_form.working_days_label') }}</label>
                    <p class="text-xs text-ttu-gray mb-2.5">{{ __('admin_doctor_form.working_days_hint') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($dayLabels as $dayNum => $label)
                            <label class="flex items-center gap-1.5 text-xs font-bold rounded-lg neu-pressed bg-ttu-cream px-3 py-2 cursor-pointer">
                                <input type="checkbox" name="working_days[]" value="{{ $dayNum }}"
                                       {{ in_array($dayNum, $selectedDays, true) ? 'checked' : '' }}
                                       class="rounded text-ttu-red focus:ring-ttu-red/30">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full btn-hero justify-center">{{ __('admin_doctor_form.create.submit') }}</button>
            </form>

        </div>
    </div>
</div>
@endsection