<?php

return [

    'title' => 'لوحة المدير',

    'stats' => [
        'students' => 'طالب مسجل',
        'staff' => 'موظف مسجل',
        'doctors' => 'طبيب',
        'today_bookings' => 'حجز اليوم',
    ],

    'analytics_heading' => 'الإحصائيات والتحليلات',

    'week_chart' => [
        'heading' => 'حجوزات الأسبوع',
        'total' => 'المجموع: :total',
        'subheading' => 'التوزيع اليومي (السبت–الجمعة)',
    ],

    'students_series' => 'طلاب',
    'staff_series' => 'موظفون',
    'role_chart' => [
        'heading' => 'طلاب مقابل موظفين',
        'subheading' => 'حجوزات هذا الأسبوع حسب الفئة',
    ],

    'hourly_chart' => [
        'heading' => 'نسبة الإشغال حسب الساعة',
        'description' => 'نسبة الخانات المحجوزة من إجمالي الخانات المتاحة لكل ساعة (على كامل السجل التاريخي)',
        'occupancy_suffix' => '% إشغال',
    ],

    'busiest' => [
        'heading' => 'الأكثر ازدحامًا',
        'empty' => 'لا توجد بيانات كافية بعد',
    ],

    'errors' => [
        'cannot_disable_admin' => 'لا يمكن تعطيل حساب المدير.',
    ],

    'flash' => [
        'user_activated' => 'تم تفعيل حساب :name.',
        'user_deactivated' => 'تم تعطيل حساب :name.',
        'doctor_created' => 'تم إضافة حساب الطبيب بنجاح.',
        'doctor_updated' => 'تم تحديث حساب ":name".',
        'medication_added' => 'تمت إضافة الدواء بنجاح.',
        'medication_edited' => 'تم تحديث بيانات الدواء بنجاح.',
        'medication_restocked' => 'تمت إضافة :amount إلى مخزون ":name".',
        'medication_activated' => 'تم تفعيل ":name".',
        'medication_deactivated' => 'تم تعطيل ":name".',
    ],

];
