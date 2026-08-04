@extends('layouts.main')

@section('title', 'تعديل حساب دكتور')

@section('content')

<x-app-header />

@php
    $dayLabels = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
    $currentDays = $doctor->doctorSchedule->working_days ?? [];
    $selectedDays = collect(old('working_days', $currentDays))->map(fn ($d) => (int) $d)->all();
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-8">تعديل حساب: {{ $doctor->name }}</h2>

        @include('partials.admin-nav')

        <div class="max-w-md rounded-[2.5rem] neu-raised-white p-8">

            @if ($errors->any())
                <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-4">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.doctors.update', $doctor) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">الاسم الكامل</label>
                    <input type="text" name="name" value="{{ old('name', $doctor->name) }}" required
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email', $doctor->email) }}" required
                           class="w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ttu-black mb-1.5">أيام العمل الأسبوعية</label>
                    <p class="text-xs text-ttu-gray mb-2.5">تحدد الأيام التي يُتوقع فيها حضور الدكتور</p>
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

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('admin.users') }}"
                       class="flex-1 neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold py-3 rounded-xl text-center">
                        إلغاء
                    </a>
                    <button type="submit" class="flex-1 btn-hero justify-center !py-3">حفظ التعديلات</button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection
