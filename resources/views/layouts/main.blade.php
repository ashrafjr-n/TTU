<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.supported_locales')[app()->getLocale()]['dir'] }}">
<head>
    @php
        // العنوان/الوصف يُلتقطان مرة واحدة هنا ليُعاد استخدامهما في وسوم
        // Open Graph بدل تكرار @yield — والافتراضي مترجم مثل بقية النصوص.
        $pageTitle = trim($__env->yieldContent('title', __('common.app_title')));
        $pageDescription = trim($__env->yieldContent('meta_description', __('common.seo.description')));
        $ogImage = asset('images/TTU-Clinic.png');
    @endphp

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">

    {{-- الفافيكون — نفس لوجو العيادة المستخدم بالهيدر وشاشة الترحيب --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">

    {{-- Open Graph — للمشاركة على واتساب/فيسبوك ولوحات الجامعة --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ __('common.seo.site_name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_JO' : 'en_US' }}">
    <meta name="twitter:card" content="summary">

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body bg-ttu-cream text-ttu-black antialiased transition-colors duration-300">

    {{-- ============ شاشة الترحيب — مقصورة على الصفحة الرئيسية (/) وحدها:
         تظهر في كل دخول/تحديث لها، ولا تظهر إطلاقًا ببقية الصفحات ولا عند
         التنقل بينها. اللوجوان يظهران مباشرة بلا بطاقة أو خلفية خلفهما.
         النصان العربي/الإنجليزي هنا مقصودان ثابتان معًا (ترحيب بلغتين)
         وليسا مرتبطين بلغة الموقع الحالية، لذلك لا يمران عبر __() كبقية
         النصوص. ============ --}}
    @if (request()->routeIs('home'))
    <div id="splashScreen"
         class="splash-screen fixed inset-0 z-[300] flex items-center justify-center bg-ttu-cream px-6 py-10">
        <div class="flex flex-col items-center text-center">

            <div class="splash-rise flex items-center justify-center gap-6 sm:gap-10 mb-10 sm:mb-12">
                <img src="{{ asset('images/TTU-logo.png') }}" alt="Tafila Technical University"
                     class="h-24 sm:h-32 lg:h-36 w-auto">
                <span class="w-px h-16 sm:h-24 bg-ttu-gray/20" aria-hidden="true"></span>
                <img src="{{ asset('images/TTU-Clinic.png') }}" alt="TTU Clinic"
                     class="h-28 sm:h-36 lg:h-40 w-auto">
            </div>

            <p dir="rtl" lang="ar"
               class="splash-rise splash-rise-2 font-display text-2xl sm:text-3xl font-extrabold text-ttu-black mb-2">
                مرحبًا بك في موقع عيادة الجامعة
            </p>
            <p dir="ltr" lang="en"
               class="splash-rise splash-rise-3 text-sm sm:text-base text-ttu-gray mb-12">
                Welcome to the university clinic website
            </p>

            <div class="splash-rise splash-rise-4 flex items-center gap-2" aria-hidden="true">
                <span class="loading-dot w-2.5 h-2.5 rounded-full bg-ttu-red"></span>
                <span class="loading-dot loading-dot-2 w-2.5 h-2.5 rounded-full bg-ttu-red"></span>
                <span class="loading-dot loading-dot-3 w-2.5 h-2.5 rounded-full bg-ttu-red"></span>
            </div>

        </div>
    </div>
    <script>
        (function () {
            var splash = document.getElementById('splashScreen');
            if (!splash) return;

            document.body.classList.add('overflow-hidden');

            function dismiss() {
                splash.classList.add('is-hiding');

                // انتظار نهاية انتقال الإخفاء نفسه بدل مؤقت ثانٍ يخمّن مدته —
                // مع مهلة احتياطية لو لم يُطلق transitionend (تفضيل تقليل الحركة).
                var done = false;
                function finish() {
                    if (done) return;
                    done = true;
                    splash.remove();
                    document.body.classList.remove('overflow-hidden');
                }

                splash.addEventListener('transitionend', finish, { once: true });
                setTimeout(finish, 900);
            }

            setTimeout(dismiss, 1600);
        })();
    </script>
    @endif

    @yield('content')

    {{-- ويدجت الدعم — عام على كل الصفحات (طبقات ثابتة مترجمة + وضع محادثة) --}}
    <x-support-widget />
</body>
</html>