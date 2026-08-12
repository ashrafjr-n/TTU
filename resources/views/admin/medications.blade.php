@extends('layouts.main')

@section('title', __('admin_medications.title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-8">{{ __('admin_medications.heading') }}</h2>

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 dark:text-green-400 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 dark:text-red-400 text-sm px-5 py-3.5 mb-6">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @include('partials.admin-nav')

        {{-- فورم إضافة دواء جديد --}}
        <div class="rounded-[2rem] neu-raised-white p-6 mb-6">
            <h3 class="font-bold text-sm mb-4">{{ __('admin_medications.add_new') }}</h3>
            <form method="POST" action="{{ route('admin.medications.store') }}" class="flex flex-wrap gap-3">
                @csrf
                <input type="text" name="name_ar" value="{{ old('name_ar') }}" placeholder="{{ __('admin_medications.name_ar_placeholder') }}" required
                       class="flex-1 min-w-[160px] rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                <input type="text" name="name_en" value="{{ old('name_en') }}" placeholder="{{ __('admin_medications.name_en_placeholder') }}" required
                       class="flex-1 min-w-[160px] rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity') }}" min="0" placeholder="{{ __('admin_medications.initial_stock_placeholder') }}" required
                       class="w-44 rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                <input type="text" name="unit" value="{{ old('unit') }}" placeholder="{{ __('admin_medications.unit_placeholder') }}"
                       class="w-44 rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 10) }}" min="0" placeholder="{{ __('admin_medications.threshold_placeholder') }}" required
                       class="w-36 rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                <button type="submit" class="btn-hero !py-2.5 text-sm">{{ __('admin_medications.add_button') }}</button>
            </form>
        </div>

        {{-- القائمة --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="space-y-3">
                @forelse ($medications as $m)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4
                        {{ $m->isLowStock() ? '!bg-red-50 dark:!bg-red-500/15' : '' }} {{ !$m->is_active ? 'opacity-60' : '' }}">
                        <div class="flex items-center gap-4">
                            <span class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-bold text-ttu-red">
                                {{ mb_substr($m->name, 0, 1) }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ttu-black">{{ $m->name }}</p>
                                <p class="text-xs text-ttu-gray mt-0.5">
                                    {{ __('admin_medications.unit_label') }}: {{ $m->unit ?: '—' }} · {{ __('admin_medications.threshold_label') }}: {{ $m->low_stock_threshold }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="text-center rounded-xl neu-pressed px-4 py-2 min-w-[90px]">
                                <p class="text-[10px] text-ttu-gray">{{ __('admin_medications.current_stock') }}</p>
                                <p class="text-sm font-bold {{ $m->isLowStock() ? 'text-red-600 dark:text-red-400' : 'text-ttu-black' }}">{{ $m->stock_quantity }}</p>
                            </div>

                            @if ($m->isLowStock())
                                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400">{{ __('admin_medications.low_stock') }}</span>
                            @endif

                            @unless ($m->is_active)
                                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400">{{ __('admin_medications.inactive') }}</span>
                            @endunless

                            {{-- إضافة كمية --}}
                            <form method="POST" action="{{ route('admin.medications.restock', $m) }}" class="flex items-center gap-1.5">
                                @csrf
                                <input type="number" name="amount" min="1" required placeholder="{{ __('admin_medications.restock_placeholder') }}"
                                       class="w-20 rounded-lg neu-pressed bg-ttu-cream border-0 px-2 py-2 text-sm text-center outline-none">
                                <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-black text-xs font-bold px-3 py-2 rounded-lg whitespace-nowrap">
                                    {{ __('admin_medications.restock_button') }}
                                </button>
                            </form>

                            <button type="button" onclick="document.getElementById('edit-med-{{ $m->id }}').classList.toggle('hidden')"
                                    class="neu-icon-btn bg-ttu-cream text-ttu-black text-xs font-bold px-3 py-2 rounded-lg">
                                {{ __('admin_medications.edit') }}
                            </button>

                            <form method="POST" action="{{ route('admin.medications.toggle', $m) }}">
                                @csrf
                                <button type="submit" class="neu-icon-btn text-xs font-bold px-3 py-2 rounded-lg {{ $m->is_active ? 'bg-ttu-cream text-ttu-red hover:!bg-ttu-red hover:!text-white' : 'bg-ttu-cream text-green-600 dark:text-green-400 hover:!bg-green-600 hover:!text-white' }}">
                                    {{ $m->is_active ? __('admin_medications.toggle.deactivate') : __('admin_medications.toggle.activate') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- فورم التعديل (مخفي افتراضيًا) --}}
                    <div id="edit-med-{{ $m->id }}" class="hidden rounded-2xl neu-pressed p-5">
                        <form method="POST" action="{{ route('admin.medications.update', $m) }}" class="flex flex-wrap gap-3 items-end">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="medication_id" value="{{ $m->id }}">
                            <div class="flex-1 min-w-[160px]">
                                <label class="block text-xs font-bold text-ttu-gray mb-1">{{ __('admin_medications.edit_form.name_ar_label') }}</label>
                                <input type="text" name="name_ar" value="{{ old('name_ar', $m->name_ar) }}" required
                                       class="w-full rounded-lg border-0 bg-white dark:bg-ttu-white px-3 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                            </div>
                            <div class="flex-1 min-w-[160px]">
                                <label class="block text-xs font-bold text-ttu-gray mb-1">{{ __('admin_medications.edit_form.name_en_label') }}</label>
                                <input type="text" name="name_en" value="{{ old('name_en', $m->name_en) }}" required
                                       class="w-full rounded-lg border-0 bg-white dark:bg-ttu-white px-3 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                            </div>
                            <div class="w-32">
                                <label class="block text-xs font-bold text-ttu-gray mb-1">{{ __('admin_medications.edit_form.unit_label') }}</label>
                                <input type="text" name="unit" value="{{ old('unit', $m->unit) }}"
                                       class="w-full rounded-lg border-0 bg-white dark:bg-ttu-white px-3 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                            </div>
                            <div class="w-32">
                                <label class="block text-xs font-bold text-ttu-gray mb-1">{{ __('admin_medications.edit_form.threshold_label') }}</label>
                                <input type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', $m->low_stock_threshold) }}" required
                                       class="w-full rounded-lg border-0 bg-white dark:bg-ttu-white px-3 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                            </div>
                            <button type="submit" class="btn-hero !py-2.5 !px-5 text-sm">{{ __('admin_medications.edit_form.save') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="text-center text-sm text-ttu-gray py-10">{{ __('admin_medications.empty') }}</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $medications->links() }}
            </div>
        </div>

    </div>
</div>

@if ($errors->any() && old('medication_id'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var panel = document.getElementById('edit-med-{{ old('medication_id') }}');
            if (panel) panel.classList.remove('hidden');
        });
    </script>
@endif

@endsection
