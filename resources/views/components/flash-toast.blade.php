{{--
    إشعار منبثق عام (Toast) — يقرأ مفتاح الجلسة 'toast' وحده، وهو مفتاح مستقل
    عن 'success'/'error' اللذين تعرضهما بعض الصفحات كشريط داخل المحتوى، حتى لا
    تظهر الرسالة مرتين على صفحة تعرض الاثنين.

    شكل البيانات المتوقَّع: ['title' => ..., 'message' => ..., 'type' => 'success'|'error']

    يظهر أعلى الصفحة في الوسط (لا أسفل يمينها) كي لا يزاحم زر ويدجت الدعم
    العائم، وz أعلى من مودال "لديك حجز حاليًا" (z-[100]) كي يبقى مقروءًا فوقه.
--}}
@php
    $toast = session('toast');
    $toastType = ($toast['type'] ?? 'success') === 'error' ? 'error' : 'success';
@endphp

@if ($toast)
<div id="flashToast" role="status" aria-live="polite"
     class="flash-toast fixed top-6 inset-x-0 z-[200] flex justify-center px-4 pointer-events-none">

    <div class="pointer-events-auto w-full max-w-sm rounded-[1.75rem] neu-raised-white p-5 flex items-start gap-4 text-start">

        <div @class([
                'w-11 h-11 shrink-0 rounded-2xl neu-icon flex items-center justify-center',
                'bg-green-500' => $toastType === 'success',
                'bg-ttu-red' => $toastType === 'error',
            ])>
            @if ($toastType === 'success')
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            @else
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            @if (!empty($toast['title']))
                <p class="font-display text-sm font-extrabold text-ttu-black mb-1">{{ $toast['title'] }}</p>
            @endif
            <p class="text-sm text-ttu-gray leading-relaxed">{{ $toast['message'] ?? '' }}</p>
        </div>

        <button type="button" onclick="dismissFlashToast()"
                title="{{ __('booking.toast.close') }}" aria-label="{{ __('booking.toast.close') }}"
                class="shrink-0 w-8 h-8 rounded-full neu-icon-btn bg-ttu-cream text-ttu-gray flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

    </div>
</div>

<script>
    function dismissFlashToast() {
        const toast = document.getElementById('flashToast');
        if (!toast) return;

        toast.classList.add('is-hiding');
        setTimeout(() => toast.remove(), 350);
    }

    // إخفاء تلقائي بعد مهلة قراءة مريحة — زر الإغلاق يبقى متاحًا قبلها
    setTimeout(dismissFlashToast, 6000);
</script>
@endif
