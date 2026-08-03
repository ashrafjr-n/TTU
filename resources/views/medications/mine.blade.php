@extends('layouts.main')

@section('title', 'أدويتي')

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-4xl mx-auto px-6 py-16 lg:py-20">

        <div class="flex items-center justify-between gap-4 mb-10">
            <div>
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">سجل طبي</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">أدويتي</h2>
                <p class="mt-1 text-sm text-ttu-gray">تقارير الزيارات والأدوية التي وصفها الطبيب</p>
            </div>

            <a href="{{ route('dashboard') }}"
               class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2.5 rounded-xl shrink-0">
                رجوع للوحة
            </a>
        </div>

        @if ($reports->isEmpty())
            <div class="rounded-[2.5rem] neu-raised-white p-8 text-center py-16">
                <div class="w-16 h-16 rounded-full neu-pressed flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-ttu-gray" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <rect x="3" y="9" width="18" height="6" rx="3" />
                        <line x1="12" y1="9" x2="12" y2="15" />
                    </svg>
                </div>
                <p class="text-sm text-ttu-gray">لا توجد تقارير زيارة حتى الآن</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($reports as $report)
                    <div class="rounded-[2.5rem] neu-raised-white p-8">

                        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-ttu-red" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-ttu-black">
                                        {{ $report->booking->booking_date->translatedFormat('d F Y') }}
                                    </p>
                                    <p class="text-xs text-ttu-gray mt-0.5">
                                        الساعة {{ sprintf('%d:%02d', $report->booking->booking_hour, $report->booking->booking_minute) }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-green-600 bg-green-50 rounded-full px-3 py-1.5">تقرير مكتمل</span>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4 mb-6">
                            <div class="rounded-xl neu-pressed px-4 py-3">
                                <p class="text-[11px] text-ttu-gray mb-1">الحالة</p>
                                <p class="text-sm text-ttu-black leading-relaxed">{{ $report->condition }}</p>
                            </div>
                            <div class="rounded-xl neu-pressed px-4 py-3">
                                <p class="text-[11px] text-ttu-gray mb-1">الفحص</p>
                                <p class="text-sm text-ttu-black leading-relaxed">{{ $report->examination }}</p>
                            </div>
                            @if ($report->diagnosis)
                                <div class="rounded-xl neu-pressed px-4 py-3">
                                    <p class="text-[11px] text-ttu-gray mb-1">التشخيص</p>
                                    <p class="text-sm text-ttu-black leading-relaxed">{{ $report->diagnosis }}</p>
                                </div>
                            @endif
                            @if ($report->treatment_plan)
                                <div class="rounded-xl neu-pressed px-4 py-3">
                                    <p class="text-[11px] text-ttu-gray mb-1">خطة العلاج</p>
                                    <p class="text-sm text-ttu-black leading-relaxed">{{ $report->treatment_plan }}</p>
                                </div>
                            @endif
                            @if ($report->notes)
                                <div class="rounded-xl neu-pressed px-4 py-3 sm:col-span-2">
                                    <p class="text-[11px] text-ttu-gray mb-1">ملاحظات</p>
                                    <p class="text-sm text-ttu-black leading-relaxed">{{ $report->notes }}</p>
                                </div>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-bold text-ttu-gray mb-3">الأدوية الموصوفة</p>

                            @if ($report->medications->isEmpty())
                                <p class="text-xs text-ttu-gray">لم توصف أدوية لهذه الزيارة</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($report->medications as $medication)
                                        <div class="flex items-center justify-between rounded-xl neu-pressed px-4 py-3">
                                            <span class="text-sm font-bold text-ttu-black">{{ $medication->name }}</span>
                                            <span class="text-xs font-bold text-ttu-red">
                                                الكمية: {{ $medication->pivot->quantity }}{{ $medication->unit ? ' '.$medication->unit : '' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

@endsection
