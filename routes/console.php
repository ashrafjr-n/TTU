<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تحقق كل دقيقة من الطلبات المنتهية الصلاحية وحولها تلقائيًا
Schedule::command('bookings:expire-requests')->everyMinute();