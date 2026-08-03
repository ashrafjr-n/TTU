@extends('layouts.main')

@section('title', 'حجز موعد')

@section('content')

@include('partials.app-header')

@php
    $roleLabel = auth()->user()->isStudent() ? 'الطالب' : 'الموظف';
    $dashboardRoute = auth()->user()->isStudent() ? route('dashboard.student') : route('dashboard.staff');
@endphp

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-4xl mx-auto px-6 py-16 lg:py-20">

        {{-- ============ رأس الصفحة ============ --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div>
                <a href="{{ $dashboardRoute }}" class="text-sm text-ttu-gray hover:text-ttu-red transition-colors mb-3 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    رجوع للرئيسية
                </a>
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">حجز {{ $roleLabel }}</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">احجز موعدك</h2>
            </div>

            <div class="rounded-2xl neu-pressed px-5 py-4 text-center">
                <p class="text-[11px] text-ttu-gray mb-1">رسوم الحجز</p>
                <p class="text-lg font-extrabold text-ttu-red">0.25 د.أ</p>
            </div>
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

        @if (!$activeBooking)
            {{-- ============ مفتاح الألوان ============ --}}
            <div class="flex flex-wrap items-center gap-4 mb-6 px-2">
                <span class="flex items-center gap-2 text-xs text-ttu-gray">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span> متاح
                </span>
                <span class="flex items-center gap-2 text-xs text-ttu-gray">
                    <span class="w-3 h-3 rounded-full bg-ttu-red"></span> محجوز
                </span>
                <span class="flex items-center gap-2 text-xs text-ttu-gray">
                    <span class="w-3 h-3 rounded-full bg-ttu-gray/40"></span> انتهى وقته
                </span>
                @if (auth()->user()->isStudent())
                    <span class="flex items-center gap-2 text-xs text-ttu-gray">
                        <span class="w-3 h-3 rounded-full bg-ttu-yellow"></span> وقت إضافي محرر من حصة الموظفين
                    </span>
                @endif
            </div>

            {{-- ============ خانات الأوقات (كل ساعة مقسّمة لـ5 دقائق) ============ --}}
            <div class="space-y-4">
                @foreach ($hours as $hourBlock)
                    <div class="rounded-[2rem] neu-raised-white p-6 sm:p-7">
                        <p class="text-sm font-bold text-ttu-black mb-4">{{ $hourBlock['label'] }}</p>

                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($hourBlock['slots'] as $slot)
                                @php
                                    $state = $slot['is_taken']
                                        ? 'taken'
                                        : ($slot['is_past'] ? 'past' : 'available');
                                @endphp

                                @if ($state === 'available')
                                    <button type="button"
                                            onclick="openBookModal({{ $slot['hour'] }}, {{ $slot['minute'] }}, '{{ $slot['time_label'] }}')"
                                            @class([
                                                'neu-icon-btn rounded-xl px-3.5 py-2.5 text-xs font-bold transition-colors',
                                                'bg-ttu-cream text-ttu-yellow hover:!bg-ttu-yellow hover:!text-white' => $slot['released'],
                                                'bg-ttu-cream text-green-600 hover:!bg-green-600 hover:!text-white' => !$slot['released'],
                                            ])
                                            title="{{ $slot['released'] ? 'وقت إضافي محرر من حصة الموظفين' : 'احجز هذا الوقت' }}">
                                        {{ $slot['time_label'] }}
                                    </button>

                                @elseif ($state === 'taken')
                                    <span class="neu-pressed rounded-xl px-3.5 py-2.5 text-xs font-bold text-ttu-red/70">
                                        {{ $slot['time_label'] }}
                                    </span>

                                @else
                                    <span class="neu-pressed rounded-xl px-3.5 py-2.5 text-xs font-bold text-ttu-gray/50">
                                        {{ $slot['time_label'] }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

@if (!$activeBooking)
    {{-- ============ مودال تأكيد الحجز ============ --}}
    <div id="bookModalOverlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/40 backdrop-blur-sm px-4">
        <div id="bookModalCard" class="w-full max-w-sm rounded-[2rem] neu-raised-white p-8 text-center scale-95 opacity-0 transition-all duration-300">

            <div class="w-16 h-16 rounded-full neu-icon bg-ttu-cream flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </div>

            <h3 class="font-display text-xl font-extrabold mb-2">تأكيد الحجز</h3>
            <p class="text-sm text-ttu-gray mb-8">
                هل تريد تأكيد حجز موعدك الساعة <span id="bookModalTime" class="font-bold text-ttu-black"></span>؟
            </p>

            <form id="bookModalForm" method="POST" action="{{ route('booking.store') }}" class="flex gap-3">
                @csrf
                <input type="hidden" name="hour" id="bookModalHour" value="">
                <input type="hidden" name="minute" id="bookModalMinute" value="">

                <button type="button" onclick="closeBookModal()"
                        class="flex-1 neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold py-3 rounded-xl">
                    إلغاء
                </button>
                <button type="submit"
                        class="flex-1 neu-icon-btn bg-green-600 text-white text-sm font-bold py-3 rounded-xl hover:!bg-green-700">
                    تأكيد الحجز
                </button>
            </form>

        </div>
    </div>

    <script>
        function openBookModal(hour, minute, timeLabel) {
            document.getElementById('bookModalHour').value = hour;
            document.getElementById('bookModalMinute').value = minute;
            document.getElementById('bookModalTime').textContent = timeLabel;

            const overlay = document.getElementById('bookModalOverlay');
            const card = document.getElementById('bookModalCard');

            overlay.classList.remove('hidden');
            overlay.classList.add('flex');

            requestAnimationFrame(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            });
        }

        function closeBookModal() {
            const overlay = document.getElementById('bookModalOverlay');
            const card = document.getElementById('bookModalCard');

            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            }, 200);
        }

        document.getElementById('bookModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeBookModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeBookModal();
        });
    </script>
@else
    {{-- ============ مودال "لديك حجز حاليًا" — يظهر مباشرة بدل قائمة الأوقات ============ --}}
    <div id="activeBookingModalOverlay" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm px-4">
        <div id="activeBookingModalCard" class="relative w-full max-w-sm rounded-[2rem] neu-raised-white p-8 text-center scale-100 opacity-100 transition-all duration-300">

            <button type="button" onclick="closeActiveBookingModal()" title="إغلاق"
                    class="absolute top-5 left-5 w-8 h-8 rounded-full neu-icon-btn bg-ttu-cream text-ttu-gray flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="w-16 h-16 rounded-full neu-icon bg-blue-500 flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h3 class="font-display text-xl font-extrabold mb-2">لديك حجز حاليًا</h3>
            <p class="text-sm text-ttu-gray mb-1">لا يمكن حجز موعد جديد قبل إلغاء موعدك الحالي:</p>
            <p class="text-lg font-bold text-ttu-black mb-8">
                {{ $activeBooking['date_label'] }} — الساعة {{ $activeBooking['time_label'] }}
            </p>

            <div class="flex gap-3">
                <button type="button" onclick="closeActiveBookingModal()"
                        class="flex-1 neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold py-3 rounded-xl">
                    إغلاق
                </button>

                <form method="POST" action="{{ route('booking.destroy', $activeBooking['id']) }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full neu-icon-btn bg-ttu-red text-white text-sm font-bold py-3 rounded-xl hover:!bg-ttu-red-dark">
                        إلغاء هذا الحجز
                    </button>
                </form>
            </div>

        </div>
    </div>

    <script>
        function closeActiveBookingModal() {
            const overlay = document.getElementById('activeBookingModalOverlay');
            const card = document.getElementById('activeBookingModalCard');

            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');

            setTimeout(() => overlay.remove(), 200);
        }

        document.getElementById('activeBookingModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeActiveBookingModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeActiveBookingModal();
        });
    </script>
@endif

@endsection
