@extends('layouts.main')

@section('title', 'حضور الأطباء')

@section('content')

<x-app-header />

@php
    $dayLabels = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
    $isToday = $selectedDate->isToday();
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-8">حضور الأطباء</h2>

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @include('partials.admin-nav')

        {{-- ============ المناوبون غدًا ============ --}}
        <div class="rounded-[2rem] neu-raised-white p-6 mb-6">
            <h3 class="font-bold text-sm mb-4">المناوبون غدًا ({{ $tomorrow->translatedFormat('d F Y') }} — {{ $dayLabels[$tomorrow->dayOfWeek] }})</h3>

            @if ($onDutyTomorrow->isEmpty())
                <p class="text-sm text-ttu-gray">لا يوجد أطباء مجدولون غدًا</p>
            @else
                <div class="flex flex-wrap gap-3">
                    @foreach ($onDutyTomorrow as $doctor)
                        <span class="text-sm font-bold px-4 py-2 rounded-xl neu-pressed text-ttu-black">{{ $doctor->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ============ سجل يوم مختار ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8 mb-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h3 class="font-display text-lg font-bold">
                    {{ $isToday ? 'قائمة اليوم' : 'سجل يوم' }} — {{ $selectedDate->translatedFormat('d F Y') }}
                    <span class="text-sm font-normal text-ttu-gray">({{ $dayLabels[$selectedDate->dayOfWeek] }})</span>
                </h3>

                <form method="GET" action="{{ route('admin.attendance') }}" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}"
                           class="rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                    <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2 rounded-xl">
                        عرض
                    </button>
                    @unless ($isToday)
                        <a href="{{ route('admin.attendance') }}" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2 rounded-xl whitespace-nowrap">
                            اليوم
                        </a>
                    @endunless
                </form>
            </div>

            <div class="space-y-3">
                @foreach ($roster as $entry)
                    @php
                        $doctor = $entry['doctor'];
                        $attendance = $entry['attendance'];
                    @endphp
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4">
                        <div class="flex items-center gap-4">
                            <span class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-bold text-ttu-red">
                                {{ mb_substr($doctor->name, 0, 1) }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ttu-black">{{ $doctor->name }}</p>
                                <p class="text-xs text-ttu-gray mt-0.5">{{ $doctor->email }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full {{ $entry['scheduled'] ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                {{ $entry['scheduled'] ? 'مجدول' : 'غير مجدول' }}
                            </span>

                            @if ($isToday && $entry['on_duty_now'])
                                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-green-50 text-green-600 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span> على رأس العمل الآن
                                </span>
                            @endif

                            @if ($attendance)
                                <div class="text-center rounded-xl neu-raised-white px-4 py-2 min-w-[110px]">
                                    <p class="text-[10px] text-ttu-gray">الحضور</p>
                                    <p class="text-sm font-bold text-ttu-black">{{ $attendance->check_in_at->format('H:i') }}</p>
                                </div>
                                <div class="text-center rounded-xl neu-raised-white px-4 py-2 min-w-[110px]">
                                    <p class="text-[10px] text-ttu-gray">الانصراف</p>
                                    <p class="text-sm font-bold text-ttu-black">
                                        {{ $attendance->check_out_at ? $attendance->check_out_at->format('H:i') : '—' }}
                                    </p>
                                </div>
                                @if ($attendance->is_auto_checkout)
                                    <span class="text-[11px] font-bold text-ttu-red bg-red-50 rounded-full px-2.5 py-1.5 whitespace-nowrap">
                                        انصراف تلقائي
                                    </span>
                                @endif
                            @elseif ($entry['scheduled'])
                                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-red-50 text-red-600">لم يحضر</span>
                            @else
                                <span class="text-xs text-ttu-gray">لا يوجد سجل</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ جدول عمل الأطباء (عرض فقط) ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <h3 class="font-display text-lg font-bold">جدول عمل الأطباء الأسبوعي</h3>
                <p class="text-xs text-ttu-gray">
                    أيام العمل تُعيَّن من صفحة تعديل حساب الدكتور
                </p>
            </div>

            <div class="space-y-4">
                @foreach ($doctors as $doctor)
                    @php
                        $workingDays = $doctor->doctorSchedule->working_days ?? [];
                    @endphp
                    <div class="rounded-2xl neu-pressed px-5 py-4 flex flex-col lg:flex-row lg:items-center gap-4">

                        <p class="text-sm font-bold text-ttu-black w-40 shrink-0">{{ $doctor->name }}</p>

                        <div class="flex flex-wrap gap-2 flex-1">
                            @if (empty($workingDays))
                                <span class="text-xs text-ttu-gray py-2">لم تُعيَّن أيام عمل</span>
                            @else
                                @foreach ($dayLabels as $dayNum => $label)
                                    @if (in_array($dayNum, $workingDays, true))
                                        <span class="text-xs font-bold rounded-lg bg-blue-50 text-blue-600 px-3 py-2">{{ $label }}</span>
                                    @endif
                                @endforeach
                            @endif
                        </div>

                        <a href="{{ route('admin.doctors.edit', $doctor) }}"
                           class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2 rounded-xl shrink-0">
                            تعديل
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
