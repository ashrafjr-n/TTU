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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body bg-ttu-cream text-ttu-black antialiased transition-colors duration-300">
    @yield('content')
</body>
</html>