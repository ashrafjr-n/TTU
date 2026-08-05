@extends('layouts.main')

@section('title', __('auth_forms.register.page_title'))

@section('content')

@include('partials.auth-header')

@php
    $roleLabels = [
        'student' => __('common.roles.student'),
        'staff'   => __('common.roles.staff'),
        'doctor'  => __('common.roles.doctor'),
    ];
    $roleIcons = [
        'student' => '🎓',
        'staff'   => '💼',
        'doctor'  => '🩺',
    ];
@endphp

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-14 bg-ttu-cream">

    <div class="w-full max-w-md rounded-[2.5rem] neu-raised-white p-8">

        <a href="{{ route('home') }}" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors mb-4 inline-block">
            &larr; {{ __('auth_forms.back') }}
        </a>

        {{-- شارة الدور المقفول — مغروزة --}}
        <div class="flex items-center justify-between rounded-2xl neu-pressed px-4 py-3 mb-6">
            <div class="flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream flex items-center justify-center text-lg">
                    {{ $roleIcons[$role] }}
                </span>
                <div class="leading-tight">
                    <p class="text-[11px] text-ttu-gray">{{ __('auth_forms.account_type') }}</p>
                    <p class="text-sm font-bold text-ttu-black">{{ $roleLabels[$role] }}</p>
                </div>
            </div>
            <a href="{{ route('home') }}#roles" class="text-xs font-semibold text-ttu-red hover:underline">
                {{ __('auth_forms.change_type') }}
            </a>
        </div>

        <div class="text-center mb-6">
            <h2 class="font-display text-2xl font-extrabold">{{ __('auth_forms.register.heading', ['role' => $roleLabels[$role]]) }}</h2>
        </div>

        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 dark:text-red-400 text-sm px-4 py-3 mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 dark:text-red-400 text-sm px-4 py-3 mb-4">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="role" value="{{ $role }}">

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.register.full_name') }}</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            @if ($role === 'student')
                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.register.student_id') }}</label>
                    <input type="text" name="identifier" value="{{ old('identifier') }}" required
                           inputmode="numeric" pattern="\d{8}" maxlength="8"
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition"
                           placeholder="{{ __('auth_forms.register.student_id_placeholder') }}">
                    <p class="text-xs text-ttu-gray mt-1.5">{{ __('auth_forms.register.student_id_help') }}</p>
                </div>
            @elseif ($role === 'staff')
                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.register.staff_id') }}</label>
                    <input type="text" name="identifier" value="{{ old('identifier') }}" required
                           inputmode="numeric" pattern="\d{4}" maxlength="4"
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition"
                           placeholder="{{ __('auth_forms.register.staff_id_placeholder') }}">
                    <p class="text-xs text-ttu-gray mt-1.5">{{ __('auth_forms.register.staff_id_help') }}</p>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.register.email') }}</label>
                <input type="email" name="email"
                       value="{{ old('email', $role === 'doctor' ? 'doctor@ttu.edu.jo' : '') }}"
                       {{ $role === 'doctor' ? 'readonly' : '' }} required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition {{ $role === 'doctor' ? 'text-ttu-gray' : '' }}">
                @if ($role === 'doctor')
                    <p class="text-xs text-ttu-gray mt-1.5">{{ __('auth_forms.register.doctor_email_help') }}</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.register.password') }}</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.register.confirm_password') }}</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            <button type="submit" class="w-full btn-hero justify-center">
                {{ __('auth_forms.register.submit') }}
            </button>
        </form>

        <p class="text-center text-sm text-ttu-gray mt-6">
            {{ __('auth_forms.register.have_account') }}
            <a href="{{ route('login') }}" class="text-ttu-red font-semibold hover:underline">{{ __('auth_forms.register.login_now') }}</a>
        </p>

    </div>
</div>
@endsection