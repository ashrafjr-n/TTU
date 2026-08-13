<div class="flex flex-wrap gap-2 mb-10">
    <a href="{{ route('admin.dashboard') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.dashboard') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.overview') }}
    </a>
    <a href="{{ route('admin.users') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.users') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.users') }}
    </a>
    <a href="{{ route('admin.medications') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.medications') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.medications') }}
    </a>
    <a href="{{ route('admin.attendance') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.attendance') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.attendance') }}
    </a>
    <a href="{{ route('admin.activity-log') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.activity-log') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.activity_log') }}
    </a>
    <a href="{{ route('admin.day-assignments') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.day-assignments') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.day_assignments') }}
    </a>
    <a href="{{ route('admin.booking-history') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.booking-history') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.booking_history') }}
    </a>
    <a href="{{ route('admin.messages') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.messages') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.messages') }}
    </a>
    <a href="{{ route('admin.doctors.create') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.doctors.create') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.add_doctor') }}
    </a>
</div>
