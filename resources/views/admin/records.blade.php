@extends('layouts.main')

@section('title', 'سجلات الجامعة')

@section('content')

@include('partials.app-header')

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-8">سجلات الجامعة</h2>

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 text-sm px-5 py-3.5 mb-6">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @include('partials.admin-nav')

        {{-- فورم إضافة رقم جديد --}}
        <div class="rounded-[2rem] neu-raised-white p-6 mb-6">
            <h3 class="font-bold text-sm mb-4">إضافة رقم جديد</h3>
            <form method="POST" action="{{ route('admin.records.store') }}" class="flex flex-wrap gap-3">
                @csrf
                <input type="text" name="identifier" placeholder="الرقم الجامعي أو الوظيفي" required
                       class="flex-1 min-w-[200px] rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm outline-none">
                <select name="type" required class="rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-2.5 text-sm outline-none">
                    <option value="student">طالب</option>
                    <option value="staff">موظف</option>
                </select>
                <button type="submit" class="btn-hero !py-2.5 text-sm">إضافة</button>
            </form>
        </div>

        {{-- القائمة --}}
        <div class="rounded-[2.5rem] neu-raised-white p-6 sm:p-8">
            <div class="space-y-3">
                @forelse ($records as $record)
                    <div class="flex items-center justify-between gap-4 rounded-2xl neu-pressed px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-ttu-black">{{ $record->identifier }}</p>
                            <p class="text-xs text-ttu-gray mt-0.5">{{ $record->type == 'student' ? 'طالب' : 'موظف' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.records.destroy', $record) }}"
                              onsubmit="return confirm('متأكد من حذف هذا الرقم؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="neu-icon-btn w-9 h-9 rounded-full bg-ttu-cream text-ttu-red flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-center text-sm text-ttu-gray py-10">لا يوجد سجلات</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $records->links() }}
            </div>
        </div>

    </div>
</div>
@endsection