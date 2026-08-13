{{--
    مودال "لديك حجز حاليًا" — مشترك بين صفحة /booking (autoOpen = true، يظهر
    فورًا لأن الصفحة لا تعرض شيئًا آخر خلفه) ولوحات التحكم (autoOpen = false،
    يُفتح فقط عند الضغط على "حجز موعد" عبر openActiveBookingModal()).
--}}
@php
    $autoOpen = $autoOpen ?? true;
@endphp

<div id="activeBookingModalOverlay"
     class="fixed inset-0 z-[100] {{ $autoOpen ? 'flex' : 'hidden' }} items-center justify-center bg-black/40 backdrop-blur-sm px-4">
    <div id="activeBookingModalCard"
         @class([
             'relative w-full max-w-sm rounded-[2rem] neu-raised-white p-8 text-center transition-all duration-300',
             'scale-100 opacity-100' => $autoOpen,
             'scale-95 opacity-0' => !$autoOpen,
         ])>

        <button type="button" onclick="closeActiveBookingModal()" title="{{ __('booking.active_modal.close') }}"
                class="absolute top-5 left-5 w-8 h-8 rounded-full neu-icon-btn bg-ttu-cream text-ttu-gray flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="w-16 h-16 rounded-full neu-icon bg-blue-500 flex items-center justify-center mx-auto mb-5">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h3 class="font-display text-xl font-extrabold mb-2">{{ __('booking.active_modal.heading') }}</h3>
        <p class="text-sm text-ttu-gray mb-1">{{ __('booking.active_modal.intro') }}</p>
        <p class="text-lg font-bold text-ttu-black mb-1.5">
            {{ $activeBooking['date_label'] }} — {{ __('booking.active_modal.hour_prefix') }} {{ $activeBooking['time_label'] }}
        </p>

        {{-- الطبيب المسؤول عن ذلك اليوم (من تعيينات الأيام، لا من الحجز نفسه) --}}
        <p class="text-sm font-bold text-ttu-gray mb-8">
            {{ __('booking.active_modal.with_doctor', ['doctor' => $activeBooking['doctor_name']]) }}
        </p>

        <div class="flex gap-3">
            <button type="button" onclick="closeActiveBookingModal()"
                    class="flex-1 neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold py-3 rounded-xl">
                {{ __('booking.active_modal.close') }}
            </button>

            <form method="POST" action="{{ route('booking.destroy', $activeBooking['id']) }}" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full neu-icon-btn bg-ttu-red text-white text-sm font-bold py-3 rounded-xl hover:!bg-ttu-red-dark">
                    {{ __('booking.active_modal.cancel_booking') }}
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    function openActiveBookingModal() {
        const overlay = document.getElementById('activeBookingModalOverlay');
        const card = document.getElementById('activeBookingModalCard');

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');

        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    }

    function closeActiveBookingModal() {
        const overlay = document.getElementById('activeBookingModalOverlay');
        const card = document.getElementById('activeBookingModalCard');

        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }, 200);
    }

    document.getElementById('activeBookingModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeActiveBookingModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeActiveBookingModal();
    });
</script>
