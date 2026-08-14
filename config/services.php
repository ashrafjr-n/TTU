<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq — مساعد الدعم (وضع "محادثة" في ويدجت الدعم)
    |--------------------------------------------------------------------------
    |
    | المفتاح يُقرأ من .env فقط ولا يُكتب أبدًا داخل أي ملف يُرفع للمستودع.
    | لو كان المفتاح فارغًا، يعمل الويدجت طبيعيًا بكل طبقاته الثابتة ويُظهر
    | رسالة "المحادثة المباشرة غير متاحة" عند فتح وضع المحادثة فقط.
    |
    | daily_limit: سقف يومي صارم لعدد الطلبات (الخطة المجانية محدودة) —
    | العدّاد بجدول chatbot_usage، صف لكل يوم (راجع ChatbotService).
    |
    */

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'timeout' => (int) env('GROQ_TIMEOUT', 12),
        'daily_limit' => (int) env('CHATBOT_DAILY_LIMIT', 40),
    ],

];
