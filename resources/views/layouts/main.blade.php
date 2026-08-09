<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.supported_locales')[app()->getLocale()]['dir'] }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.app_title'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- تطبيق الوضع الليلي قبل الرسم لتفادي "الوميض" (FOUC) --}}
    <script>
        (function () {
            var stored = localStorage.getItem('ttu-theme');
            var isDark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>

    {{-- إخفاء شاشة الترحيب فورًا قبل أي رسم لو سبق عرضها بهذا المتصفح —
         بنفس منطق منع "الوميض" أعلاه، حتى لا تظهر ولو لجزء من الثانية. --}}
    <style>
        html.splash-hide #splashScreen { display: none !important; }
    </style>
    <script>
        (function () {
            if (localStorage.getItem('ttu-splash-shown')) {
                document.documentElement.classList.add('splash-hide');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body bg-ttu-cream text-ttu-black antialiased transition-colors duration-300">

    {{-- ============ شاشة الترحيب — تظهر مرة واحدة فقط أول زيارة لهذا
         المتصفح على الإطلاق (أي صفحة)، ثم لا تظهر ثانية إطلاقًا بفضل
         localStorage. النصان العربي/الإنجليزي هنا مقصودان ثابتان معًا
         (ترحيب بلغتين) وليسا مرتبطين بلغة الموقع الحالية، لذلك لا يمران
         عبر __() كبقية النصوص. ============ --}}
    <div id="splashScreen"
         class="fixed inset-0 z-[300] flex items-center justify-center bg-ttu-cream px-6 transition-opacity duration-700">
        <div class="flex flex-col items-center text-center max-w-sm">

            <div class="flex items-center justify-center gap-4 mb-8">
                <div class="rounded-2xl logo-badge p-3">
                    <img src="{{ asset('images/TTU-logo.png') }}" alt="Tafila Technical University" class="h-14 w-auto">
                </div>
                <div class="w-px h-12 bg-ttu-gray/25"></div>
                <img src="{{ asset('images/TTU-Clinic.png') }}" alt="TTU Clinic" class="h-16 w-auto">
            </div>

            <p dir="rtl" lang="ar" class="font-display text-xl sm:text-2xl font-extrabold text-ttu-black mb-1.5">
                مرحبًا بك في موقع عيادة الجامعة
            </p>
            <p dir="ltr" lang="en" class="text-sm sm:text-base text-ttu-gray mb-10">
                Welcome to the university clinic website
            </p>

            <div class="flex items-center gap-2" aria-hidden="true">
                <span class="loading-dot w-2.5 h-2.5 rounded-full bg-ttu-red"></span>
                <span class="loading-dot loading-dot-2 w-2.5 h-2.5 rounded-full bg-ttu-red"></span>
                <span class="loading-dot loading-dot-3 w-2.5 h-2.5 rounded-full bg-ttu-red"></span>
            </div>

        </div>
    </div>
    <script>
        (function () {
            if (localStorage.getItem('ttu-splash-shown')) return;

            var splash = document.getElementById('splashScreen');
            if (!splash) return;

            // تُسجَّل فورًا (لا بعد انتهاء التلاشي) كي لا تظهر مجددًا لو
            // المستخدم غادر الصفحة بالتنقل السريع قبل اكتمال العرض/التلاشي
            localStorage.setItem('ttu-splash-shown', '1');

            document.body.classList.add('overflow-hidden');

            setTimeout(function () {
                splash.style.opacity = '0';

                setTimeout(function () {
                    splash.remove();
                    document.body.classList.remove('overflow-hidden');
                }, 700);
            }, 1600);
        })();
    </script>

    @yield('content')
</body>
</html>