@extends('layouts.main')

@section('title', 'إنشاء حساب')

@section('content')

@include('partials.auth-header')

@php
    $roleLabels = [
        'student' => 'طالب',
        'staff'   => 'موظف',
        'doctor'  => 'دكتور',
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
            &larr; رجوع
        </a>

        {{-- شارة الدور المقفول — مغروزة --}}
        <div class="flex items-center justify-between rounded-2xl neu-pressed px-4 py-3 mb-6">
            <div class="flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-full neu-icon bg-ttu-cream flex items-center justify-center text-lg">
                    {{ $roleIcons[$role] }}
                </span>
                <div class="leading-tight">
                    <p class="text-[11px] text-ttu-gray">نوع الحساب</p>
                    <p class="text-sm font-bold text-ttu-black">{{ $roleLabels[$role] }}</p>
                </div>
            </div>
            <a href="{{ route('home') }}#roles" class="text-xs font-semibold text-ttu-red hover:underline">
                تغيير النوع
            </a>
        </div>

        <div class="text-center mb-6">
            <h2 class="font-display text-2xl font-extrabold">إنشاء حساب {{ $roleLabels[$role] }}</h2>
        </div>

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

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="role" value="{{ $role }}">

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">الاسم الكامل</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            @if ($role === 'student')
                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">الرقم الجامعي</label>
                    <input type="text" name="identifier" value="{{ old('identifier') }}" required
                           inputmode="numeric" pattern="\d{8}" maxlength="8"
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition"
                           placeholder="مثال: 20210123">
                    <p class="text-xs text-ttu-gray mt-1.5">8 أرقام بالضبط، سيتم التحقق منه عبر سجلات الجامعة</p>
                </div>
            @elseif ($role === 'staff')
                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">الرقم الوظيفي</label>
                    <input type="text" name="identifier" value="{{ old('identifier') }}" required
                           inputmode="numeric" pattern="\d{4}" maxlength="4"
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition"
                           placeholder="مثال: 2320">
                    <p class="text-xs text-ttu-gray mt-1.5">4 أرقام بالضبط، سيتم التحقق منه عبر سجلات الجامعة</p>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">البريد الإلكتروني</label>
                <input type="email" name="email"
                       value="{{ old('email', $role === 'doctor' ? 'doctor@ttu.edu.jo' : '') }}"
                       {{ $role === 'doctor' ? 'readonly' : '' }} required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition {{ $role === 'doctor' ? 'text-ttu-gray' : '' }}">
                @if ($role === 'doctor')
                    <p class="text-xs text-ttu-gray mt-1.5">بريد ثابت مخصص لحساب الطبيب</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">كلمة المرور</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-ttu-black mb-1.5">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none transition">
            </div>

            <button type="submit" class="w-full btn-hero justify-center">
                إنشاء الحساب
            </button>
        </form>

        <p class="text-center text-sm text-ttu-gray mt-6">
            لديك حساب بالفعل؟
            <a href="{{ route('login') }}" class="text-ttu-red font-semibold hover:underline">سجّل دخولك</a>
        </p>

    </div>
</div>
@endsection