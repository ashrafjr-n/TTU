<div class="flex flex-wrap gap-2 mb-10">
    <a href="{{ route('admin.dashboard') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.dashboard') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        نظرة عامة
    </a>
    <a href="{{ route('admin.users') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.users') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        المستخدمون
    </a>
    <a href="{{ route('admin.records') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.records') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        سجلات الجامعة
    </a>
    <a href="{{ route('admin.doctors.create') }}"
       class="neu-icon-btn px-5 py-2.5 rounded-xl text-sm font-bold {{ request()->routeIs('admin.doctors.create') ? '!bg-ttu-red !text-white' : 'bg-ttu-cream text-ttu-black' }}">
        + إضافة دكتور
    </a>
</div>