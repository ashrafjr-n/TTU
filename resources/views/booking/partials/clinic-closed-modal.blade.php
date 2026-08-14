{{--
    مودال "العيادة مغلقة اليوم" — يظهر بدل قائمة الأوقات في صفحة /booking حين
    تكون نافذة الحجز مغلقة فعليًا (Booking::isBookingWindowClosed: من الخميس
    الساعة 4 عصرًا حتى نهاية الجمعة). لا يشمل هذا السبت — السبت يعيد فتح
    الحجز لأول يومي عمل بالأسبوع القادم بدل إغلاق الصفحة كليًا.

    $clinicClosedMessage (من Booking::closedWindowDescription()) يُبنى من
    نفس ثوابت isBookingWindowClosed()، فلا يتكرر هنا نص أيام مكتوب حرفيًا
    قد يفترق عن المنطق الفعلي لاحقًا.

    نفس بنية مودال الحد الفصلي (لا autoOpen — يفتح دائمًا) لأن الحالتين
    تتشاركان المعنى: لا شيء للحجز الآن على هذه الصفحة.
--}}
<div id="clinicClosedModalOverlay"
     class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm px-4">
    <div id="clinicClosedModalCard"
         class="relative w-full max-w-sm rounded-[2rem] neu-raised-white p-8 text-center scale-100 opacity-100 transition-all duration-300">

        <button type="button" onclick="closeClinicClosedModal()" title="{{ __('booking.closed_modal.close') }}"
                class="absolute top-5 left-5 w-8 h-8 rounded-full neu-icon-btn bg-ttu-cream text-ttu-gray flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="w-16 h-16 rounded-full neu-icon bg-ttu-gray flex items-center justify-center mx-auto mb-5">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
        </div>

        <h3 class="font-display text-xl font-extrabold mb-2">{{ __('booking.closed_modal.heading') }}</h3>
        <p class="text-sm text-ttu-gray mb-8">{{ $clinicClosedMessage }}</p>

        <a href="{{ route('dashboard') }}"
           class="w-full inline-flex items-center justify-center neu-icon-btn bg-ttu-red text-white text-sm font-bold py-3 rounded-xl hover:!bg-ttu-red-dark">
            {{ __('common.buttons.back_to_dashboard') }}
        </a>

    </div>
</div>

<script>
    function closeClinicClosedModal() {
        const overlay = document.getElementById('clinicClosedModalOverlay');
        const card = document.getElementById('clinicClosedModalCard');

        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }, 200);
    }

    document.getElementById('clinicClosedModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeClinicClosedModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeClinicClosedModal();
    });
</script>
