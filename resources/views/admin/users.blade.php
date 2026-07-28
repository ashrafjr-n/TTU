@extends('layouts.main')

@section('title', 'المستخدمون')

@section('content')

@include('partials.app-header')

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-8">إدارة المستخدمين</h2>

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">{{ session('error') }}</div>
        @endif

        @include('partials.admin-nav')

        {{-- فلترة وبحث --}}
        <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap gap-3 mb-6">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث بالاسم أو البريد أو الرقم..."
                   class="flex-1 min-w-[220px] rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
            <select name="role" onchange="this.form.submit()"
                    class="rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm outline-none">
                <option value="">كل الأدوار</option>
                <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>طالب</option>
                <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>موظف</option>
                <option value="doctor" {{ request('role') == 'doctor' ? 'selected' : '' }}>دكتور</option>
            </select>
            <button type="submit" class="neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold px-6 rounded-xl">بحث</button>
        </form>

        {{-- قائمة المستخدمين --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="space-y-3">
                @forelse ($users as $u)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4">
                        <div class="flex items-center gap-4">
                            <span class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-bold text-ttu-red">
                                {{ mb_substr($u->name, 0, 1) }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ttu-black">{{ $u->name }}</p>
                                <p class="text-xs text-ttu-gray mt-0.5">{{ $u->email }} · {{ $u->identifier }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed
                                {{ $u->role == 'student' ? 'text-blue-600' : ($u->role == 'staff' ? 'text-green-600' : 'text-purple-600') }}">
                                {{ $u->role == 'student' ? 'طالب' : ($u->role == 'staff' ? 'موظف' : 'دكتور') }}
                            </span>

                            <span class="text-xs font-bold px-3 py-1.5 rounded-full {{ $u->is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' }}">
                                {{ $u->is_active ? 'مفعّل' : 'معطّل' }}
                            </span>

                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}">
                                @csrf
                                <button type="submit" class="neu-icon-btn text-sm font-bold px-4 py-2 rounded-xl {{ $u->is_active ? 'bg-ttu-cream text-ttu-red hover:!bg-ttu-red hover:!text-white' : 'bg-ttu-cream text-green-600 hover:!bg-green-600 hover:!text-white' }}">
                                    {{ $u->is_active ? 'تعطيل' : 'تفعيل' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-sm text-ttu-gray py-10">لا يوجد مستخدمون مطابقون</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $users->withQueryString()->links() }}
            </div>
        </div>

    </div>
</div>
@endsection