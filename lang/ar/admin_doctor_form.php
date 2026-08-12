<?php

return [

    'create' => [
        'page_title' => 'إضافة طبيب',
        'heading' => 'إضافة حساب طبيب جديد',
        'submit' => 'إضافة الطبيب',
    ],

    'edit' => [
        'page_title' => 'تعديل حساب طبيب',
        'heading' => 'تعديل حساب: :name',
        'cancel' => 'إلغاء',
        'submit' => 'حفظ التعديلات',
    ],

    'errors' => [
        // الأرقام المحجوزة لحسابات الزرع الثابتة — إعطاؤها لطبيب جديد يمنع
        // UserSeeder من إسناد معرّفاته لاحقًا فيتوقف الزرع بالكامل.
        'reserved_identifier' => 'هذا الرقم محجوز لحساب ثابت بالنظام. اختر رقمًا آخر.',
    ],

    'name_label' => 'الاسم الكامل',
    'identifier_label' => 'الرقم الوظيفي',
    'identifier_placeholder' => 'مثال: 405',
    'password_label' => 'كلمة المرور',
    'password_confirmation_label' => 'تأكيد كلمة المرور',
    'working_days_label' => 'أيام العمل الأسبوعية',
    'working_days_hint' => 'تحدد الأيام التي يُتوقع فيها حضور الطبيب',

];
