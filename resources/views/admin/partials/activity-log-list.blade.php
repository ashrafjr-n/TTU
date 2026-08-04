{{--
    قائمة سجل نشاط مشتركة — تُستخدم في صفحة نشاط مستخدم معيّن (admin/user-activity)
    وصفحة سجل نشاط الإدارة العام (admin/activity-log). تتوقع متغيّر $logs
    (مجموعة مقسّمة لصفحات من ActivityLog) و$showActor اختياري (bool) لعرض
    اسم الفاعل ودوره بكل صف — مفيد بصفحة السجل العام حيث الفاعل يتغيّر بين صف وآخر.
--}}
@php
    $showActor = $showActor ?? false;
@endphp

<div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
    <div class="space-y-3">
        @forelse ($logs as $log)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 rounded-2xl neu-pressed px-5 py-4">
                <div>
                    <p class="text-sm font-bold text-ttu-black">{{ $log->renderedDescription() ?? $log->action }}</p>
                    @if ($showActor)
                        <p class="text-xs text-ttu-gray mt-0.5">
                            {{ $log->user->name ?? __('admin_activity_log.deleted_user') }}
                            @if ($log->user)
                                · {{ __('common.roles.'.$log->user->role) }}
                            @endif
                        </p>
                    @endif
                </div>
                <span class="text-xs text-ttu-gray whitespace-nowrap shrink-0">
                    {{ $log->created_at->translatedFormat('d F Y — H:i') }}
                </span>
            </div>
        @empty
            <div class="text-center py-10">
                <div class="w-16 h-16 rounded-full neu-pressed flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-ttu-gray" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-sm text-ttu-gray">{{ __('admin_activity_log.empty') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
