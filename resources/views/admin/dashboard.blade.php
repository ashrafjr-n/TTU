@extends('layouts.main')

@section('title', 'لوحة المدير')

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        {{-- بطاقة البروفايل --}}
        <div class="relative rounded-[2.5rem] neu-raised-white p-8 mb-10 flex items-center gap-6">
            <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-black to-ttu-black flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
                </svg>
            </div>
            <div>
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">لوحة الإدارة</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">مرحبًا، {{ auth()->user()->name }} 👋</h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ auth()->user()->email }}</p>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">{{ session('error') }}</div>
        @endif

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

        @include('partials.admin-nav')

        {{-- بطاقات الإحصائيات --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_students'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">طالب مسجل</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_staff'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">موظف مسجل</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_doctors'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">دكتور</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['today_bookings'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">حجز اليوم</p>
            </div>
        </div>

        {{-- ============ الإحصائيات والتحليلات ============ --}}
        <h3 class="font-display text-lg font-bold mb-6">الإحصائيات والتحليلات</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- الحجوزات هذا الأسبوع --}}
            <div class="rounded-[2rem] neu-raised-white p-6">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="font-bold text-sm text-ttu-black">حجوزات الأسبوع</h4>
                    <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed text-ttu-red">
                        المجموع: {{ $weekBookingsTotal }}
                    </span>
                </div>
                <p class="text-xs text-ttu-gray mb-4">التوزيع اليومي (الأحد–السبت)</p>
                <div class="h-64">
                    <canvas id="weekChartCanvas"></canvas>
                </div>
            </div>

            {{-- طلاب مقابل موظفين --}}
            <div class="rounded-[2rem] neu-raised-white p-6">
                <h4 class="font-bold text-sm text-ttu-black mb-1">طلاب مقابل موظفين</h4>
                <p class="text-xs text-ttu-gray mb-4">حجوزات هذا الأسبوع حسب الفئة</p>
                <div class="h-64">
                    <canvas id="roleChartCanvas"></canvas>
                </div>
            </div>
        </div>

        {{-- نسبة الإشغال حسب الساعة + أكثر الساعات ازدحامًا --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="flex flex-col lg:flex-row lg:items-start gap-8">

                <div class="flex-1">
                    <h4 class="font-bold text-sm text-ttu-black mb-1">نسبة الإشغال حسب الساعة</h4>
                    <p class="text-xs text-ttu-gray mb-4">نسبة الخانات المحجوزة من إجمالي الخانات المتاحة لكل ساعة (على كامل السجل التاريخي)</p>
                    <div class="h-72">
                        <canvas id="hourlyChartCanvas"></canvas>
                    </div>
                </div>

                <div class="lg:w-64 shrink-0">
                    <h4 class="font-bold text-sm text-ttu-black mb-4">الأكثر ازدحامًا</h4>
                    <div class="space-y-3">
                        @forelse ($busiestHours as $i => $entry)
                            <div class="flex items-center justify-between gap-3 rounded-2xl neu-pressed px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-extrabold text-xs text-ttu-red">
                                        {{ $i + 1 }}
                                    </span>
                                    <span class="text-sm font-bold text-ttu-black">{{ $entry['label'] }}</span>
                                </div>
                                <span class="text-sm font-bold text-ttu-red">{{ $entry['rate'] }}%</span>
                            </div>
                        @empty
                            <p class="text-sm text-ttu-gray">لا توجد بيانات كافية بعد</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

@vite(['resources/js/admin-charts.js'])

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const blue = '#1D4ED8';
        const blueDark = '#1E40AF';
        const blueSoft = 'rgba(29, 78, 216, 0.45)';
        const gray = '#9CA3AF';
        const gridColor = 'rgba(10, 10, 10, 0.06)';

        Chart.defaults.font.family = "'IBM Plex Sans Arabic', sans-serif";
        Chart.defaults.color = '#6B6B6B';

        const weekChart = @json($weekChart);
        const roleChart = @json($roleChart);
        const hourlyChart = @json($hourlyChart);
        const busiestHours = @json($busiestHours->pluck('hour'));

        new Chart(document.getElementById('weekChartCanvas'), {
            type: 'bar',
            data: {
                labels: weekChart.labels,
                datasets: [{
                    data: weekChart.data,
                    backgroundColor: blue,
                    borderRadius: 10,
                    maxBarThickness: 34,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0A0A0A', padding: 10, cornerRadius: 10 },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor }, border: { display: false } },
                },
            },
        });

        new Chart(document.getElementById('roleChartCanvas'), {
            type: 'doughnut',
            data: {
                labels: roleChart.labels,
                datasets: [{
                    data: roleChart.data,
                    backgroundColor: [blue, gray],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                    tooltip: { backgroundColor: '#0A0A0A', padding: 10, cornerRadius: 10 },
                },
            },
        });

        new Chart(document.getElementById('hourlyChartCanvas'), {
            type: 'bar',
            data: {
                labels: hourlyChart.labels,
                datasets: [{
                    data: hourlyChart.rates,
                    backgroundColor: hourlyChart.hours.map((h) => busiestHours.includes(h) ? blueDark : blueSoft),
                    borderRadius: 10,
                    maxBarThickness: 40,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0A0A0A',
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: { label: (ctx) => ctx.parsed.y + '% إشغال' },
                    },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: (v) => v + '%' },
                        grid: { color: gridColor },
                        border: { display: false },
                    },
                },
            },
        });
    });
</script>

@endsection