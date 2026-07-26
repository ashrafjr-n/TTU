@extends('layouts.main')

@section('title', 'حجز موعد')

@section('content')
<div class="min-h-screen bg-gray-50">

    <nav class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-800">عيادة TTU</h1>
        <a href="{{ auth()->user()->isStudent() ? route('dashboard.student') : route('dashboard.staff') }}"
           class="text-sm text-gray-500 hover:underline">&larr; رجوع للرئيسية</a>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-10">

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800">احجز موعدك</h2>
            <p class="text-gray-500 mt-1">سعر الحجز: <span class="font-semibold text-blue-600">0.25 د.أ (ربع دينار)</span></p>
        </div>

        {{-- رسائل النجاح / الخطأ --}}
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="space-y-4">
            @foreach ($slots as $slot)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">

                    <div>
                        <p class="text-lg font-semibold text-gray-800">{{ $slot['time_label'] }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            طلاب: {{ $slot['student_booked'] }}/{{ $slot['student_capacity'] }}
                            &nbsp;·&nbsp;
                            موظفين: {{ $slot['staff_booked'] }}/{{ $slot['staff_capacity'] }}
                        </p>
                    </div>

                    <div>
                        @if ($slot['already_booked'])
                            <span class="bg-blue-50 text-blue-600 text-sm font-medium px-5 py-2 rounded-lg">
                                ✓ لديك حجز بهذا الوقت
                            </span>

                        @elseif ($slot['pending_request'])
                            <span class="bg-amber-50 text-amber-600 text-sm font-medium px-5 py-2 rounded-lg">
                                ⏱ طلبك قيد المراجعة
                            </span>

                        @elseif ($slot['can_book_directly'])
                            <form method="POST" action="{{ route('booking.store') }}">
                                @csrf
                                <input type="hidden" name="hour" value="{{ $slot['hour'] }}">
                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                    احجز الآن
                                </button>
                            </form>

                        @elseif ($slot['can_request'])
                            <form method="POST" action="{{ route('booking.request') }}">
                                @csrf
                                <input type="hidden" name="hour" value="{{ $slot['hour'] }}">
                                <button type="submit"
                                        class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                    إرسال طلب حجز
                                </button>
                            </form>

                        @else
                            <span class="bg-gray-100 text-gray-400 text-sm font-medium px-5 py-2 rounded-lg cursor-not-allowed">
                                محجوز بالكامل
                            </span>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection