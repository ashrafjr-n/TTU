<?php

return [

    'title' => 'حجز موعد',

    'heading' => [
        'student' => 'حجز الطالب',
        'staff' => 'حجز الموظف',
    ],

    'book_appointment' => 'احجز موعدك',
    'fee_label' => 'رسوم الدواء',
    'fee_value' => '0.20 د.أ لكل دواء',

    'legend' => [
        'available' => 'متاح',
        'taken' => 'محجوز',
        'past' => 'انتهى وقته',
        'released' => 'وقت إضافي محرر من حصة الموظفين',
    ],

    'choose_day' => 'اختر اليوم',
    'choose_hour' => 'اختر الساعة',
    'book_this_slot' => 'احجز هذا الوقت',

    'day' => [
        'today' => 'اليوم',
        'tomorrow' => 'غدًا',
        'day_after' => 'بعد الغد',
    ],

    'confirm_modal' => [
        'heading' => 'تأكيد الحجز',
        'question_before' => 'هل تريد تأكيد حجز موعدك',
        'question_middle' => 'الساعة',
        'question_end' => '؟',
        'cancel' => 'إلغاء',
        'confirm' => 'تأكيد الحجز',
    ],

    'active_modal' => [
        'close' => 'إغلاق',
        'heading' => 'لديك حجز حاليًا',
        'intro' => 'لا يمكن حجز موعد جديد قبل إلغاء موعدك الحالي:',
        'hour_prefix' => 'الساعة',
        'cancel_booking' => 'إلغاء هذا الحجز',
    ],

    'semester_limit_modal' => [
        'close' => 'إغلاق',
        'heading' => 'بلغت الحد الأقصى لحجوزات هذا الفصل',
        'intro' => 'بلغت الحد الأقصى (3 حجوزات) لهذا الفصل الدراسي. يرجى التواصل معنا.',
    ],

    'errors' => [
        'students_only' => 'هذا الوقت مخصص للطلاب فقط.',
        'staff_only' => 'هذا الوقت مخصص للموظفين فقط.',
        'released_slot_closed' => 'انتهى وقت حجز الأوقات الإضافية المحررة لهذا اليوم.',
        'slot_expired' => 'انتهى وقت هذا الموعد.',
        'already_have_active_booking' => 'لديك حجز فعّال بالفعل. يجب إلغاؤه أولًا قبل حجز موعد جديد.',
        'semester_limit_reached' => 'بلغت الحد الأقصى (3 حجوزات) لهذا الفصل الدراسي. يرجى التواصل معنا.',
        'just_taken' => 'عذرًا، هذا الوقت تم حجزه للتو. حاول وقتًا آخر.',
        'not_authorized_to_cancel' => 'ليس لديك صلاحية إلغاء هذا الحجز.',
        'not_cancellable' => 'هذا الحجز غير قابل للإلغاء.',
    ],

    'flash' => [
        'booked_success' => 'تم حجز موعدك بنجاح!',
        'cancelled_success' => 'تم إلغاء حجزك بنجاح.',
    ],

];
