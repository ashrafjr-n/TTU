{{--
    شارة حالة حجز موحّدة — تُشتق من Booking::displayStatus() بدل عمود
    مخزَّن، فتُستخدم بنفس المنطق واللون في كل مكان تظهر فيه حالة حجز
    (لوحة الطالب/الموظف، لوحة الدكتور، سجل حجوزات المدير).
--}}
@props(['booking'])

@php
    $status = $booking->displayStatus();

    $colorClass = match ($status) {
        'confirmed', 'ended_documented' => 'bg-green-50 dark:bg-green-500/15 text-green-600 dark:text-green-400',
        'ended_undocumented' => 'bg-gray-100 dark:bg-gray-500/15 text-gray-500 dark:text-gray-400',
        'cancelled' => 'bg-red-50 dark:bg-red-500/15 text-red-500 dark:text-red-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "text-xs font-bold px-3 py-1.5 rounded-full $colorClass"]) }}>
    {{ __('common.booking_status.'.$status) }}
</span>
