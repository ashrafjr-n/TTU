@extends('layouts.main')

@section('title', __('auth_forms.confirm_password.heading'))

@section('content')

@include('partials.auth-header')

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-14 bg-ttu-cream">

    <div class="w-full max-w-md rounded-[2.5rem] neu-raised-white p-8">

        <a href="{{ route('dashboard') }}" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors mb-4 inline-block">
            &larr; {{ __('common.buttons.back_to_dashboard') }}
        </a>

        <div class="text-center mb-6">
            <h2 class="font-display text-2xl font-extrabold">{{ __('auth_forms.confirm_password.heading') }}</h2>
        </div>

        <p class="text-sm text-ttu-gray leading-relaxed mb-6">
            {{ __('auth_forms.confirm_password.intro') }}
        </p>

        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-4 py-3 mb-4">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">{{ __('auth_forms.confirm_password.password') }}</label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            <button type="submit" class="w-full btn-hero justify-center">
                {{ __('auth_forms.confirm_password.submit') }}
            </button>
        </form>

    </div>
</div>
@endsection
