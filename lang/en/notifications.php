<?php

return [

    'reminder' => [
        'title' => 'Appointment reminder',
        'body' => 'Reminder: you have an appointment today at :time (in about an hour).',
        'body_15m' => 'Reminder: your appointment at :time is in about 15 minutes.',
    ],

    'booking_cancelled' => [
        'title' => 'Your appointment was cancelled',
        'body' => 'Your appointment on :date at :time was cancelled by the clinic.',
    ],

    'visit_report' => [
        'title' => 'Your visit report is ready',
        'body' => 'Your visit report is ready. Condition: :condition. Medications: :medications.',
        'body_no_meds' => 'Your visit report is ready. Condition: :condition. No medications were prescribed.',
    ],

    'doctor_message' => [
        'title' => 'Message from :name',
    ],

    'message_reply' => [
        'title' => 'Reply from :name',
    ],

];
