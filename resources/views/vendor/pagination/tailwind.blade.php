{{--
    نسخة مخصّصة من قالب الترقيم الافتراضي (pagination::tailwind) بلغة تصميم
    الموقع النيومورفية — Laravel يبحث هنا تلقائيًا قبل الرجوع لقالب الحزمة،
    فتُطبَّق على كل صفحات الإدارة المرقّمة دفعة واحدة دون تعديل ->links()
    بكل صفحة على حدة.

    ترتيب "السابق"/"التالي" هنا هو الترتيب المنطقي فقط (بحسب تدفّق القراءة):
    السابق أولًا ثم أرقام الصفحات ثم التالي — وaHTML dir="rtl" على <html>
    يقلب flex تلقائيًا فيظهر "السابق" يمين الشاشة و"التالي" يسارها بالعربية،
    مع rtl:rotate-180 على السهمين ليستمرا يشيران للاتجاه الصحيح بصريًا.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('pagination.navigation_label') }}"
         class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        {{-- شاشات صغيرة: سابق/تالي فقط --}}
        <div class="flex items-center justify-between gap-3 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1.5 rounded-xl neu-pressed text-ttu-gray/60 text-sm font-bold px-4 py-2.5 cursor-not-allowed" aria-disabled="true">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    {{ __('pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="neu-icon-btn inline-flex items-center gap-1.5 rounded-xl bg-ttu-cream text-ttu-black text-sm font-bold px-4 py-2.5">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    {{ __('pagination.previous') }}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="neu-icon-btn inline-flex items-center gap-1.5 rounded-xl bg-ttu-cream text-ttu-black text-sm font-bold px-4 py-2.5">
                    {{ __('pagination.next') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-xl neu-pressed text-ttu-gray/60 text-sm font-bold px-4 py-2.5 cursor-not-allowed" aria-disabled="true">
                    {{ __('pagination.next') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </span>
            @endif
        </div>

        {{-- شاشات كبيرة: نص "عرض X إلى Y..." + أرقام الصفحات --}}
        <p class="hidden sm:block text-sm text-ttu-gray">
            {{ __('pagination.showing', [
                'first' => $paginator->firstItem() ?? 0,
                'last' => $paginator->lastItem() ?? $paginator->count(),
                'total' => $paginator->total(),
            ]) }}
        </p>

        <div class="hidden sm:flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl neu-pressed text-ttu-gray/60 cursor-not-allowed" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="neu-icon-btn inline-flex items-center justify-center w-9 h-9 rounded-xl bg-ttu-cream text-ttu-black" aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-ttu-gray cursor-default" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-ttu-red text-white text-sm font-bold">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('pagination.go_to_page', ['page' => $page]) }}"
                               class="neu-icon-btn inline-flex items-center justify-center w-9 h-9 rounded-xl bg-ttu-cream text-ttu-black text-sm font-bold">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="neu-icon-btn inline-flex items-center justify-center w-9 h-9 rounded-xl bg-ttu-cream text-ttu-black" aria-label="{{ __('pagination.next') }}">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl neu-pressed text-ttu-gray/60 cursor-not-allowed" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
