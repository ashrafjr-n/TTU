@extends('layouts.main')

@section('title', __('auth_forms.verify_email.heading'))

@section('content')

@include('partials.auth-header')

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-14 bg-ttu-cream">

    <div class="w-full max-w-md rounded-[2.5rem] neu-raised-white p-8">

        <a href="{{ route('dashboard') }}" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors mb-4 inline-block">
            &larr; {{ __('common.buttons.back_to_dashboard') }}
        </a>

        <div class="text-center mb-6">
            <h2 class="font-display text-2xl font-extrabold">{{ __('auth_forms.verify_email.heading') }}</h2>
        </div>

        <p class="text-sm text-ttu-gray leading-relaxed mb-6">
            {{ __('auth_forms.verify_email.intro') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="rounded-2xl neu-pressed text-green-700 dark:text-green-400 text-sm px-4 py-3 mb-6">
                {{ __('auth_forms.verify_email.link_sent') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-hero !py-2.5 !px-5 text-sm">
                    {{ __('auth_forms.verify_email.resend') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors underline">
                    {{ __('common.buttons.logout') }}
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
