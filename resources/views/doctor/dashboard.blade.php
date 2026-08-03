@extends('layouts.main')

@section('title', 'لوحة تحكم الدكتور')

@section('content')

<x-app-header />

@php
    $initial = mb_substr(auth()->user()->name, 0, 1);
    $todayCount = $bookings->count();
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        {{-- ============ بطاقة الملف الشخصي ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex flex-col sm:flex-row sm:items-center gap-6">

            <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-black to-ttu-black flex items-center justify-center shrink-0">
                <span class="font-display text-3xl font-extrabold text-white">{{ $initial }}</span>
            </div>

            <div class="flex-1">
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">لوحة الدكتور</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">
                    مرحبًا، {{ auth()->user()->name }} 👋
                </h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ auth()->user()->email }}</p>
            </div>

            {{-- شارات إحصائية سريعة --}}
            <div class="flex gap-3">
                <div class="rounded-2xl neu-pressed px-4 py-3 text-center min-w-[100px]">
                    <p class="text-[11px] text-ttu-gray mb-1">حجوزات اليوم</p>
                    <p class="text-sm font-bold text-ttu-black">{{ $todayCount }}</p>
                </div>
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
                    <h3 class="relative font-display text-base font-bold mb-1.5">تسجيل الخروج</h3>
                    <p class="relative text-xs text-ttu-gray leading-relaxed">الخروج من حسابك بأمان</p>
                </button>
            </form>

        </div>

        {{-- رسائل النجاح/الخطأ --}}
        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">
                {{ session('error') }}
            </div>
        @endif

        {{-- ============ جدول الحجوزات ============ --}}
        <div class="rounded-[2.5rem] neu-raised-white p-8">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h3 class="font-display text-lg font-bold">جدول الحجوزات</h3>

                <form method="GET" action="{{ route('dashboard.doctor') }}" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}"
                           class="rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                    <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2 rounded-xl">
                        عرض
                    </button>
                </form>
            </div>

            <div class="space-y-3">
                @forelse ($bookings as $b)
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
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed {{ $b->user->role == 'student' ? 'text-blue-600' : 'text-green-600' }}">
                                {{ $b->user->role == 'student' ? 'طالب' : 'موظف' }}
                            </span>

                            <button type="button" onclick='openVisitReportModal(@json($reportPayload))'
                                    class="neu-icon-btn bg-ttu-cream text-ttu-black text-xs font-bold px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ $existingReport ? 'تعديل التقرير' : 'إرفاق تقرير' }}
                            </button>

                            <form method="POST" action="{{ route('doctor.bookings.cancel', $b) }}"
                                  onsubmit="return confirm('متأكد من إلغاء هذا الحجز؟');">
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
                        <p class="text-sm text-ttu-gray">لا يوجد حجوزات بهذا التاريخ</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@include('doctor.partials.visit-report-modal', ['medications' => $medications])

@php
    $reopenBooking = old('booking_id') ? $bookings->firstWhere('id', (int) old('booking_id')) : null;
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
            openVisitReportModal(@json($reopenPayload));
        });
    </script>
@endif

@endsection