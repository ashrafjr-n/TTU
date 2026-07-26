@extends('layouts.main')

@section('title', 'تسجيل الدخول')

@section('content')

@include('partials.auth-header')

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-14 bg-ttu-cream">

    <div class="w-full max-w-md rounded-[2.5rem] neu-raised-white p-8">

        <a href="{{ route('home') }}" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors mb-4 inline-block">
            &larr; رجوع
        </a>

        <div class="text-center mb-8">
            <h2 class="font-display text-2xl font-extrabold">تسجيل الدخول</h2>
        </div>

        @if (session('status'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-4 py-3 mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-4 py-3 mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-4 py-3 mb-4">
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
                    البريد الإلكتروني أو الرقم الجامعي/الوظيفي
                </label>
                <input type="text" name="login" value="{{ old('login') }}" required autofocus
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition"
                       placeholder="example@ttu.edu.jo أو 20210123">
            </div>

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">كلمة المرور</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-ttu-gray">
                    <input type="checkbox" name="remember" class="rounded border-black/20">
                    تذكرني
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-ttu-red hover:underline">
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>

            <button type="submit" class="w-full btn-hero justify-center">
                تسجيل الدخول
            </button>
        </form>

        <p class="text-center text-sm text-ttu-gray mt-6">
            ليس لديك حساب؟
            <a href="{{ route('home') }}#roles" class="text-ttu-red font-semibold hover:underline">سجّل الآن</a>
        </p>

    </div>
</div>
@endsection