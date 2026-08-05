<?php

return [

    'title' => 'لوحة تحكم الطبيب',
    'badge' => 'لوحة الطبيب',
    'today_bookings' => 'حجوزات اليوم',

    'attendance' => [
        'heading' => 'حالة الحضور اليوم',
        'none_today' => 'لا يوجد سجل حضور اليوم',
        'present' => 'متواجد الآن — بدأ الدوام الساعة :time',
        'ended' => 'انتهى دوامك اليوم — من :in إلى :out',
        'auto_checkout' => 'تسجيل خروج تلقائي',
        'checkout_button' => 'تسجيل الانصراف',
        'check_in_required' => 'يجب تسجيل الحضور أولًا.',
        'already_checked_out' => 'لقد سجّلت انصرافك اليوم مسبقًا.',
        'check_out_success' => 'تم تسجيل انصرافك بنجاح.',
    ],

    'bookings_table' => [
        'heading' => 'جدول الحجوزات',
        'choose_day' => 'اختر اليوم',
        'edit_report' => 'تعديل التقرير',
        'attach_report' => 'إرفاق تقرير',
        'cancel_confirm' => 'هل أنت متأكد من إلغاء هذا الحجز؟',
        'empty' => 'لا توجد حجوزات بهذا التاريخ',
        'not_cancellable' => 'هذا الحجز غير قابل للإلغاء.',
        'cancel_success' => 'تم إلغاء الحجز وإشعار المريض.',
    ],

    'report_modal' => [
        'close' => 'إغلاق',
        'edit_title' => 'تعديل التقرير',
        'create_title' => 'إرفاق تقرير',
        'patient_name' => 'اسم المريض',
        'patient_identifier' => 'الرقم الجامعي/الوظيفي',
        'appointment_date' => 'تاريخ الموعد',
        'appointment_time' => 'وقت الموعد',
        'condition' => 'الحالة',
        'examination' => 'الفحص',
        'diagnosis' => 'التشخيص (اختياري)',
        'treatment_plan' => 'خطة العلاج (اختياري)',
        'notes' => 'ملاحظات (اختياري)',
        'medications' => 'الأدوية الموصوفة',
        'add_medication' => '+ إضافة دواء',
        'no_medications_added' => 'لا توجد أدوية مضافة',
        'search_placeholder' => 'ابحث عن دواء...',
        'no_results' => 'لا نتائج',
        'available_prefix' => 'متوفر: ',
        'cancel' => 'إلغاء',
        'save_edit' => 'حفظ التعديلات',
        'save_create' => 'حفظ التقرير',
        'not_confirmed' => 'لا يمكن إرفاق تقرير لحجز غير مؤكد.',
        'insufficient_stock' => 'الكمية المطلوبة من ":name" غير متوفرة بالمخزون (المتوفر: :stock).',
        'save_success' => 'تم حفظ تقرير الزيارة بنجاح.',
    ],

];
