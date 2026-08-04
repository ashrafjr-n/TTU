<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * يقرأ اللغة المحفوظة بالجلسة (بعد اختيار المستخدم من قائمة الهيدر) ويضبط
 * لغة التطبيق قبل عرض أي صفحة، بحيث تبقى اللغة المختارة نفسها عبر كل
 * الصفحات والطلبات اللاحقة، لا فقط الصفحة التي تم التبديل منها.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (is_string($locale) && array_key_exists($locale, config('app.supported_locales'))) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
