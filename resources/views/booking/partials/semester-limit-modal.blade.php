{{--
    مودال "بلغت الحد الأقصى لحجوزات هذا الفصل" — يظهر بدل قائمة الأوقات في
    صفحة /booking فور بلوغ المستخدم 3 حجوزات مؤكدة بالفصل الحالي. بعكس مودال
    "لديك حجز حاليًا" هذا غير مُعاد استخدامه من لوحات التحكم، فيفتح دائمًا
    تلقائيًا بلا حاجة لمعامل autoOpen.
--}}
<div id="semesterLimitModalOverlay"
     class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm px-4">
    <div id="semesterLimitModalCard"
         class="relative w-full max-w-sm rounded-[2rem] neu-raised-white p-8 text-center scale-100 opacity-100 transition-all duration-300">

        <button type="button" onclick="closeSemesterLimitModal()" title="{{ __('booking.semester_limit_modal.close') }}"
                class="absolute top-5 left-5 w-8 h-8 rounded-full neu-icon-btn bg-ttu-cream text-ttu-gray flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="w-16 h-16 rounded-full neu-icon bg-ttu-red flex items-center justify-center mx-auto mb-5">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>

        <h3 class="font-display text-xl font-extrabold mb-2">{{ __('booking.semester_limit_modal.heading') }}</h3>
        <p class="text-sm text-ttu-gray mb-8">{{ __('booking.semester_limit_modal.intro') }}</p>

        <a href="{{ route('contact') }}"
           class="w-full inline-flex items-center justify-center neu-icon-btn bg-ttu-red text-white text-sm font-bold py-3 rounded-xl hover:!bg-ttu-red-dark">
            {{ __('common.nav.contact') }}
        </a>

    </div>
</div>

<script>
    function closeSemesterLimitModal() {
        const overlay = document.getElementById('semesterLimitModalOverlay');
        const card = document.getElementById('semesterLimitModalCard');

        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }, 200);
    }

    document.getElementById('semesterLimitModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeSemesterLimitModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSemesterLimitModal();
    });
</script>
