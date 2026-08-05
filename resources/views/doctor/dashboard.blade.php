@extends('layouts.main')

@section('title', __('doctor.title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        {{-- ============ بطاقة الملف الشخصي ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex flex-col sm:flex-row sm:items-center gap-6">

            <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-black to-ttu-black dark:from-ttu-red dark:to-ttu-red-dark flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 2v2" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 2v2" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 15a6 6 0 0 0 12 0v-3" />
                    <circle cx="20" cy="10" r="2" />
                </svg>
            </div>

            <div class="flex-1">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">{{ __('doctor.badge') }}</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">
                    {{ __('dashboard.greeting', ['name' => auth()->user()->name]) }} 👋
                </h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ auth()->user()->email }}</p>
            </div>

            {{-- شارات إحصائية سريعة --}}
            <div class="flex gap-3">
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">{{ __('doctor.today_bookings') }}</p>
                    <p class="text-sm font-bold text-ttu-black">{{ $todayBookingsCount }}</p>
                </div>
            </div>
        </div>

        {{-- ============ بطاقة الحضور ============ --}}
        <div class="rounded-[2rem] neu-raised-white p-6 mb-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-ttu-gray mb-1.5">{{ __('doctor.attendance.heading') }}</p>
                @if (!$todayAttendance)
                    <p class="text-sm font-bold text-ttu-black flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-ttu-gray"></span>
                        {{ __('doctor.attendance.none_today') }}
                    </p>
                @elseif (!$todayAttendance->check_out_at)
                    <p class="text-sm font-bold text-green-600 dark:text-green-400 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        {{ __('doctor.attendance.present', ['time' => $todayAttendance->check_in_at->format('H:i')]) }}
                    </p>
                @else
                    <p class="text-sm font-bold text-ttu-black flex items-center gap-1.5 flex-wrap">
                        <span class="w-2 h-2 rounded-full bg-ttu-gray"></span>
                        {{ __('doctor.attendance.ended', ['in' => $todayAttendance->check_in_at->format('H:i'), 'out' => $todayAttendance->check_out_at->format('H:i')]) }}
                        @if ($todayAttendance->is_auto_checkout)
                            <span class="text-[11px] font-bold text-ttu-red bg-red-50 dark:bg-red-500/15 rounded-full px-2.5 py-1">{{ __('doctor.attendance.auto_checkout') }}</span>
                        @endif
                    </p>
                @endif
            </div>

            <div>
                {{-- لا يوجد زر "تسجيل الحضور": الحضور يُسجَّل تلقائيًا لحظة الدخول --}}
                @if ($todayAttendance && !$todayAttendance->check_out_at)
                    <form method="POST" action="{{ route('doctor.attendance.checkout') }}">
                        @csrf
                        <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-red text-sm font-bold px-6 py-2.5 rounded-xl hover:!bg-ttu-red hover:!text-white">
                            {{ __('doctor.attendance.checkout_button') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ============ الخدمات ============ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

            {{-- تسجيل الخروج --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover w-full text-right">
                    <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                        <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </div>
                    <h3 class="relative font-display text-base font-bold mb-1.5">{{ __('common.buttons.logout') }}</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">{{ __('common.buttons.logout_desc') }}</p>
                </button>
            </form>

        </div>

        {{-- رسائل النجاح/الخطأ --}}
        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 dark:text-green-400 text-sm px-5 py-3.5 mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 dark:text-red-400 text-sm px-5 py-3.5 mb-6">
                {{ session('error') }}
            </div>
        @endif

        {{-- ============ جدول الحجوزات ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-8">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h3 class="font-display text-lg font-bold">{{ __('doctor.bookings_table.heading') }}</h3>
            </div>

            {{-- ============ تبويبات الأيام (اليوم / غدًا / بعد الغد) ============ --}}
            <div class="flex flex-wrap gap-2.5 mb-6" role="tablist" aria-label="{{ __('doctor.bookings_table.choose_day') }}">
                @foreach ($days as $day)
                    <button type="button"
                            role="tab"
                            id="doctor-day-tab-{{ $day['index'] }}"
                            data-day="{{ $day['index'] }}"
                            aria-selected="{{ $day['index'] === 0 ? 'true' : 'false' }}"
                            onclick="selectDoctorDay({{ $day['index'] }})"
                            @class([
                                'doctor-day-tab rounded-2xl px-5 py-3 text-sm font-bold whitespace-nowrap transition-colors',
                                'neu-pressed text-ttu-red' => $day['index'] === 0,
                                'neu-icon-btn bg-ttu-cream text-ttu-black' => $day['index'] !== 0,
                            ])>
                        {{ $day['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($days as $day)
                <div id="doctor-day-panel-{{ $day['index'] }}" class="doctor-day-panel space-y-3 {{ $day['index'] === 0 ? '' : 'hidden' }}">
                    @forelse ($day['bookings'] as $b)
                        @php
                            $existingReport = $b->visitReport;
                            $reportPayload = [
                                'bookingId' => $b->id,
                                'isEdit' => (bool) $existingReport,
                                'patientName' => $b->user->name,
                                'patientIdentifier' => $b->user->identifier,
                                'dateLabel' => $b->booking_date->translatedFormat('d F Y'),
                                'timeLabel' => $b->timeLabel(),
                                'condition' => $existingReport->condition ?? '',
                                'examination' => $existingReport->examination ?? '',
                                'diagnosis' => $existingReport->diagnosis ?? '',
                                'treatmentPlan' => $existingReport->treatment_plan ?? '',
                                'notes' => $existingReport->notes ?? '',
                                'medications' => $existingReport
                                    ? $existingReport->medications->map(fn ($m) => [
                                        'medication_id' => $m->id,
                                        'name' => $m->name,
                                        'quantity' => $m->pivot->quantity,
                                    ])->values()->all()
                                    : [],
                            ];
                        @endphp
                        <div class="flex items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4">
                            <div class="flex items-center gap-4">
                                <span class="w-14 h-11 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-extrabold text-xs text-ttu-red">
                                    {{ sprintf('%d:%02d', $b->booking_hour, $b->booking_minute) }}
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-ttu-black">{{ $b->user->name }}</p>
                                    <p class="text-xs text-ttu-gray mt-0.5">{{ $b->user->identifier }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed {{ $b->user->role == 'student' ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ $b->user->role == 'student' ? __('common.roles.student') : __('common.roles.staff') }}
                                </span>

                                <button type="button" onclick='openVisitReportModal(@json($reportPayload))'
                                        class="neu-icon-btn bg-ttu-cream text-ttu-black text-xs font-bold px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ $existingReport ? __('doctor.bookings_table.edit_report') : __('doctor.bookings_table.attach_report') }}
                                </button>

                                <form method="POST" action="{{ route('doctor.bookings.cancel', $b) }}"
                                      onsubmit="return confirm('{{ __('doctor.bookings_table.cancel_confirm') }}');">
                                    @csrf
                                    <button type="submit" class="neu-icon-btn w-9 h-9 rounded-full bg-ttu-cream text-ttu-red flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10">
                            <div class="w-16 h-16 rounded-full neu-pressed flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-ttu-gray" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </div>
                            <p class="text-sm text-ttu-gray">{{ __('doctor.bookings_table.empty') }}</p>
                        </div>
                    @endforelse
                </div>
            @endforeach
        </div>

    </div>
</div>

<script>
    function selectDoctorDay(dayIndex) {
        document.querySelectorAll('.doctor-day-tab').forEach(function (tab) {
            const isActive = Number(tab.dataset.day) === dayIndex;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.classList.toggle('neu-pressed', isActive);
            tab.classList.toggle('text-ttu-red', isActive);
            tab.classList.toggle('neu-icon-btn', !isActive);
            tab.classList.toggle('bg-ttu-cream', !isActive);
            tab.classList.toggle('text-ttu-black', !isActive);
        });

        document.querySelectorAll('.doctor-day-panel').forEach(function (panel) {
            panel.classList.toggle('hidden', panel.id !== 'doctor-day-panel-' + dayIndex);
        });
    }
</script>

@include('doctor.partials.visit-report-modal', ['medications' => $medications])

@php
    // نبحث بكل الأيام الثلاثة المعروضة (بدل يوم واحد) لأن الحجز المُعاد فتحه
    // بعد خطأ تحقّق قد يخص غدًا أو بعد الغد لا اليوم فقط.
    $reopenDayIndex = null;
    $reopenBooking = null;
    if (old('booking_id')) {
        foreach ($days as $day) {
            $found = $day['bookings']->firstWhere('id', (int) old('booking_id'));
            if ($found) {
                $reopenBooking = $found;
                $reopenDayIndex = $day['index'];
                break;
            }
        }
    }
@endphp

@if ($reopenBooking)
    @php
        $reopenPayload = [
            'bookingId' => $reopenBooking->id,
            'isEdit' => (bool) $reopenBooking->visitReport,
            'patientName' => $reopenBooking->user->name,
            'patientIdentifier' => $reopenBooking->user->identifier,
            'dateLabel' => $reopenBooking->booking_date->translatedFormat('d F Y'),
            'timeLabel' => $reopenBooking->timeLabel(),
            'condition' => old('condition', ''),
            'examination' => old('examination', ''),
            'diagnosis' => old('diagnosis', ''),
            'treatmentPlan' => old('treatment_plan', ''),
            'notes' => old('notes', ''),
            'medications' => collect(old('medications', []))->values()->all(),
            'errors' => $errors->toArray(),
        ];
    @endphp
    <script>
        document.addEventListener('alpine:initialized', function () {
            selectDoctorDay({{ $reopenDayIndex }});
            openVisitReportModal(@json($reopenPayload));
        });
    </script>
@endif

@endsection