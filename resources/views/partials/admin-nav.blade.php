<div class="flex flex-wrap gap-2 mb-10">
    <a href="{{ route('admin.dashboard') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.dashboard') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.overview') }}
    </a>
    <a href="{{ route('admin.users') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.users') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.users') }}
    </a>
    <a href="{{ route('admin.records') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.records') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.records') }}
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
    <a href="{{ route('admin.doctors.create') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.doctors.create') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        {{ __('admin_common.nav.add_doctor') }}
    </a>
</div>
