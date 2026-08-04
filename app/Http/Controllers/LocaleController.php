<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * تبديل لغة الواجهة — يُستدعى من زر الكرة الأرضية بالهيدر. يحفظ
     * الاختيار بالجلسة (يبقى ساريًا على كل الصفحات اللاحقة عبر
     * Middleware SetLocale) ثم يرجع لنفس الصفحة التي جاء منها الطلب.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('app.supported_locales')), 404);

        $request->session()->put('locale', $locale);

        return back();
    }
}
