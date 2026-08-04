@extends('layouts.main')

@section('title', __('admin_dashboard.title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">{{ session('error') }}</div>
        @endif

        @include('partials.admin-nav')

        {{-- بطاقات الإحصائيات --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_students'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">{{ __('admin_dashboard.stats.students') }}</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_staff'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">{{ __('admin_dashboard.stats.staff') }}</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['total_doctors'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">{{ __('admin_dashboard.stats.doctors') }}</p>
            </div>
            <div class="rounded-[2rem] neu-raised-white p-6 text-center">
                <p class="text-3xl font-display font-extrabold text-ttu-red">{{ $stats['today_bookings'] }}</p>
                <p class="text-xs text-ttu-gray mt-2">{{ __('admin_dashboard.stats.today_bookings') }}</p>
            </div>
        </div>

        {{-- ============ الإحصائيات والتحليلات ============ --}}
        <h3 class="font-display text-lg font-bold mb-6">{{ __('admin_dashboard.analytics_heading') }}</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- الحجوزات هذا الأسبوع --}}
            <div class="rounded-[2rem] neu-raised-white p-6">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="font-bold text-sm text-ttu-black">{{ __('admin_dashboard.week_chart.heading') }}</h4>
                    <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed text-ttu-red">
                        {{ __('admin_dashboard.week_chart.total', ['total' => $weekBookingsTotal]) }}
                    </span>
                </div>
                <p class="text-xs text-ttu-gray mb-4">{{ __('admin_dashboard.week_chart.subheading') }}</p>
                <div class="h-64">
                    <canvas id="weekChartCanvas"></canvas>
                </div>
            </div>

            {{-- طلاب مقابل موظفين --}}
            <div class="rounded-[2rem] neu-raised-white p-6">
                <h4 class="font-bold text-sm text-ttu-black mb-1">{{ __('admin_dashboard.role_chart.heading') }}</h4>
                <p class="text-xs text-ttu-gray mb-4">{{ __('admin_dashboard.role_chart.subheading') }}</p>
                <div class="h-64">
                    <canvas id="roleChartCanvas"></canvas>
                </div>
            </div>
        </div>

        {{-- نسبة الإشغال حسب الساعة + أكثر الساعات ازدحامًا --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="flex flex-col lg:flex-row lg:items-start gap-8">

                <div class="flex-1">
                    <h4 class="font-bold text-sm text-ttu-black mb-1">{{ __('admin_dashboard.hourly_chart.heading') }}</h4>
                    <p class="text-xs text-ttu-gray mb-4">{{ __('admin_dashboard.hourly_chart.description') }}</p>
                    <div class="h-72">
                        <canvas id="hourlyChartCanvas"></canvas>
                    </div>
                </div>

                <div class="lg:w-64 shrink-0">
                    <h4 class="font-bold text-sm text-ttu-black mb-4">{{ __('admin_dashboard.busiest.heading') }}</h4>
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
                            <p class="text-sm text-ttu-gray">{{ __('admin_dashboard.busiest.empty') }}</p>
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
        const occupancySuffix = @json(__('admin_dashboard.hourly_chart.occupancy_suffix'));

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
                        callbacks: { label: (ctx) => ctx.parsed.y + occupancySuffix },
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