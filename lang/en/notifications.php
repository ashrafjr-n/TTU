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

    'admin_message' => [
        'title' => 'Message from :name',
    ],

    // The reply notification never carries the reply text — clicking it opens the message panel
    'clinic_reply' => [
        'title' => 'You have a reply from the clinic administration',
        'body' => 'Click to view the reply.',
    ],

];
