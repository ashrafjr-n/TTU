@extends('layouts.main')

@section('title', __('auth_forms.login.heading'))

@section('content')

@include('partials.auth-header')

@php
    $roleLabels = ['student' => __('auth_forms.login.role_student'), 'staff' => __('auth_forms.login.role_staff')];
    $roleIcons = ['student' => '🎓', 'staff' => '💼'];
@endphp

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-14 bg-ttu-cream">

    <div class="w-full max-w-md rounded-[2.5rem] neu-raised-white p-8">

        <a href="{{ route('home') }}" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors mb-4 inline-block">
            &larr; {{ __('auth_forms.back') }}
        </a>

        @if ($role)
            {{-- شارة الدور — نفس نمط صفحة إنشاء الحساب --}}
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
        @endif

        <div class="text-center mb-8">
            <h2 class="font-display text-2xl font-extrabold">
                {{ $role ? __('auth_forms.login.heading_role', ['role' => $roleLabels[$role]]) : __('auth_forms.login.heading') }}
            </h2>
        </div>

        @if (session('status'))
            <div class="rounded-2xl neu-pressed text-green-700 dark:text-green-400 text-sm px-4 py-3 mb-4">
                {{ session('status') }}
            </div>
        @endif

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

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">
                    {{ __('auth_forms.login.login_field') }}
                </label>
                <input type="text" name="login" value="{{ old('login') }}" required autofocus
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition"
                       placeholder="{{ __('auth_forms.login.login_placeholder') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.login.password') }}</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-ttu-gray">
                    <input type="checkbox" name="remember" class="rounded border-black/20 dark:border-white/20">
                    {{ __('auth_forms.login.remember') }}
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-ttu-red hover:underline">
                        {{ __('auth_forms.login.forgot_password') }}
                    </a>
                @endif
            </div>

            <button type="submit" class="w-full btn-hero justify-center">
                {{ __('auth_forms.login.submit') }}
            </button>
        </form>

        <p class="text-center text-sm text-ttu-gray mt-6">
            {{ __('auth_forms.login.no_account') }}
            <a href="{{ $role ? route('register', ['role' => $role]) : route('home').'#roles' }}"
               class="text-ttu-red font-semibold hover:underline">
                {{ __('auth_forms.login.register_now') }}
            </a>
        </p>

    </div>
</div>
@endsection