@extends('layouts.main')

@section('title', __('medications.title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">

    <div class="max-w-4xl mx-auto px-6 py-16 lg:py-20">

        <div class="flex items-center justify-between gap-4 mb-10">
            <div>
                <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">{{ __('medications.eyebrow') }}</span>
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold">{{ __('medications.heading') }}</h2>
                <p class="mt-1 text-sm text-ttu-gray">{{ __('medications.subheading') }}</p>
            </div>

            <a href="{{ route('dashboard') }}"
               class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-5 py-2.5 rounded-xl shrink-0">
                {{ __('common.buttons.back_to_dashboard') }}
            </a>
        </div>

        @if ($reports->isEmpty())
            <div class="rounded-[2.5rem] neu-raised-white p-8 text-center py-20">
                <div class="w-20 h-20 rounded-full neu-pressed flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-ttu-gray" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <rect x="3" y="9" width="18" height="6" rx="3" />
                        <line x1="12" y1="9" x2="12" y2="15" />
                    </svg>
                </div>
                <h3 class="font-display text-lg font-bold mb-2">{{ __('medications.empty.heading') }}</h3>
                <p class="text-sm text-ttu-gray max-w-sm mx-auto mb-8">
                    {{ __('medications.empty.body') }}
                </p>
                <a href="{{ route('booking.index') }}" class="btn-hero cursor-pointer">
                    {{ __('medications.empty.cta') }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>
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
                                        {{ __('medications.report.hour_prefix') }} {{ sprintf('%d:%02d', $report->booking->booking_hour, $report->booking->booking_minute) }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-green-600 bg-green-50 rounded-full px-3 py-1.5">{{ __('medications.report.completed_badge') }}</span>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4 mb-6">
                            <div class="rounded-xl neu-pressed px-4 py-3">
                                <p class="text-[11px] text-ttu-gray mb-1">{{ __('medications.report.condition') }}</p>
                                <p class="text-sm text-ttu-black leading-relaxed">{{ $report->condition }}</p>
                            </div>
                            <div class="rounded-xl neu-pressed px-4 py-3">
                                <p class="text-[11px] text-ttu-gray mb-1">{{ __('medications.report.examination') }}</p>
                                <p class="text-sm text-ttu-black leading-relaxed">{{ $report->examination }}</p>
                            </div>
                            @if ($report->diagnosis)
                                <div class="rounded-xl neu-pressed px-4 py-3">
                                    <p class="text-[11px] text-ttu-gray mb-1">{{ __('medications.report.diagnosis') }}</p>
                                    <p class="text-sm text-ttu-black leading-relaxed">{{ $report->diagnosis }}</p>
                                </div>
                            @endif
                            @if ($report->treatment_plan)
                                <div class="rounded-xl neu-pressed px-4 py-3">
                                    <p class="text-[11px] text-ttu-gray mb-1">{{ __('medications.report.treatment_plan') }}</p>
                                    <p class="text-sm text-ttu-black leading-relaxed">{{ $report->treatment_plan }}</p>
                                </div>
                            @endif
                            @if ($report->notes)
                                <div class="rounded-xl neu-pressed px-4 py-3 sm:col-span-2">
                                    <p class="text-[11px] text-ttu-gray mb-1">{{ __('medications.report.notes') }}</p>
                                    <p class="text-sm text-ttu-black leading-relaxed">{{ $report->notes }}</p>
                                </div>
                            @endif
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <p class="text-xs font-bold text-ttu-gray">{{ __('medications.report.medications') }}</p>

                                @if ($report->medications->isNotEmpty())
                                    <span class="text-xs font-bold text-ttu-red bg-red-50 rounded-full px-3 py-1.5">
                                        {{ __('medications.report.fee_line', [
                                            'price' => number_format(\App\Models\Medication::PRICE_PER_ITEM, 2),
                                            'count' => $report->medications->count(),
                                            'total' => number_format($report->medicationsFee(), 2),
                                        ]) }}
                                    </span>
                                @endif
                            </div>

                            @if ($report->medications->isEmpty())
                                <p class="text-xs text-ttu-gray">{{ __('medications.report.none_prescribed') }}</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($report->medications as $medication)
                                        <div class="flex items-center justify-between rounded-xl neu-pressed px-4 py-3">
                                            <span class="text-sm font-bold text-ttu-black">{{ $medication->name }}</span>
                                            <span class="text-xs font-bold text-ttu-red">
                                                {{ __('medications.report.quantity_prefix') }} {{ $medication->pivot->quantity }}{{ $medication->unit ? ' '.$medication->unit : '' }}
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
