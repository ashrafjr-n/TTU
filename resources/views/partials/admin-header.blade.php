{{-- صف البروفايل + تسجيل الخروج — بطاقتان جنبًا إلى جنب. الترتيب منطقي
     (البروفايل أولًا بالـDOM) فينعكس تلقائيًا: الخروج يسار البروفايل بالعربية
     ويمينه بالإنجليزية. تحت lg تعودان فوق بعضهما كالسابق. --}}
<div class="flex flex-col lg:flex-row gap-6 mb-10">

    {{-- بطاقة البروفايل --}}
    <div class="relative flex-1 rounded-[2.5rem] neu-raised-white p-8 flex items-center gap-6">
        <div class="w-20 h-20 rounded-full neu-icon bg-gradient-to-br from-ttu-black to-ttu-black dark:from-ttu-red dark:to-ttu-red-dark flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
            </svg>
        </div>
        <div>
            <span class="inline-block text-xs font-bold tracking-widest text-ttu-red mb-1.5">{{ __('admin_common.badge') }}</span>
            <h2 class="font-display text-2xl sm:text-3xl font-extrabold">{{ __('dashboard.greeting', ['name' => auth()->user()->name]) }} 👋</h2>
            <p class="mt-1 text-sm text-ttu-gray">{{ auth()->user()->email }}</p>
        </div>
    </div>

    {{-- تسجيل الخروج --}}
    <form method="POST" action="{{ route('logout') }}" class="lg:w-64 shrink-0">
        @csrf
        <button type="submit"
                class="group relative flex flex-col overflow-hidden p-7 rounded-[2rem] neu-raised-white neu-card-hover w-full h-full text-right">
            <div class="relative w-14 h-14 rounded-2xl neu-icon bg-ttu-cream flex items-center justify-center mb-5 group-hover:bg-ttu-red transition-colors duration-300">
                <svg class="neu-wiggle w-6 h-6 text-ttu-red group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
            </div>
            <h3 class="relative font-display text-base font-bold mb-1.5">{{ __('common.buttons.logout') }}</h3>
            <p class="relative text-xs text-ttu-gray leading-relaxed">{{ __('common.buttons.logout_desc') }}</p>
        </button>
    </form>

</div>
