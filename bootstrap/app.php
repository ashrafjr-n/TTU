<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (وأي مستضيف يوقف TLS عند الحافة) يمرر المخطط الحقيقي عبر
        // X-Forwarded-Proto — بدون الوثوق بالبروكسي يولّد Laravel روابط http://
        // داخل صفحة https فيحجبها المتصفح كـ mixed content.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // نهاية دوام العيادة (Booking::CLOSE_HOUR = 16) — تسجيل انصراف تلقائي
        // لأي دكتور سجّل حضوره ولم يسجّل انصرافه
        $schedule->command('attendance:auto-checkout')->dailyAt('16:00');

        // تذكير بالمواعيد التي تقترب خلال ساعة تقريبًا
        $schedule->command('notifications:send-reminders')->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();