@extends('layouts.main')

@section('title', __('admin_messages.title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-2">{{ __('admin_messages.heading') }}</h2>
        <p class="text-sm text-ttu-gray mb-8 max-w-2xl">{{ __('admin_messages.intro') }}</p>

        @include('partials.admin-nav')

        @if (session('success'))
            <div class="rounded-2xl neu-pressed text-green-700 dark:text-green-400 text-sm px-5 py-3.5 mb-6">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl neu-pressed text-red-600 dark:text-red-400 text-sm px-5 py-3.5 mb-6">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="space-y-6">
            @forelse ($messages as $message)
                <div class="rounded-[2rem] neu-raised-white p-6 sm:p-8">

                    {{-- ترويسة الرسالة: المرسِل + دوره + وقت الإرسال --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                        <div class="flex items-center gap-4">
                            <span class="w-11 h-11 rounded-full neu-icon bg-ttu-cream flex items-center justify-center shrink-0 font-display font-bold text-ttu-red">
                                {{ $message->sender->nameInitial() }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ttu-black">{{ $message->sender->name }}</p>
                                <p class="text-xs text-ttu-gray mt-0.5">{{ $message->sender->identifier }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed
                                {{ $message->sender->role === 'student' ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ __('common.roles.'.$message->sender->role) }}
                            </span>
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full neu-pressed text-ttu-black">
                                {{ $message->created_at->translatedFormat('d F Y — H:i') }}
                            </span>
                        </div>
                    </div>

                    {{-- نص الرسالة --}}
                    <div class="rounded-2xl neu-pressed px-5 py-4 text-sm text-ttu-black leading-relaxed whitespace-pre-line">{{ $message->body }}</div>

                    {{-- الردود السابقة على هذه الرسالة --}}
                    @if ($message->replies->isNotEmpty())
                        <p class="text-[11px] font-bold text-ttu-gray tracking-widest mt-6 mb-3">{{ __('admin_messages.thread.replies_heading') }}</p>
                        <div class="space-y-3">
                            @foreach ($message->replies as $reply)
                                <div class="rounded-2xl neu-pressed px-5 py-4 ms-0 sm:ms-8">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-ttu-red text-white">{{ __('admin_messages.thread.reply_badge') }}</span>
                                        <span class="text-xs font-bold text-ttu-black">{{ $reply->sender->name }}</span>
                                        <span class="text-[10px] text-ttu-gray/70">{{ $reply->created_at->translatedFormat('d F Y — H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-ttu-gray leading-relaxed whitespace-pre-line">{{ $reply->body }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- فورم الرد --}}
                    <form method="POST" action="{{ route('admin.messages.reply', $message) }}" class="mt-6">
                        @csrf
                        <label class="block text-sm font-medium text-ttu-black mb-1.5" for="reply-body-{{ $message->id }}">
                            {{ __('admin_messages.thread.reply_label') }}
                        </label>
                        <textarea id="reply-body-{{ $message->id }}" name="body" rows="3" required
                                  maxlength="{{ $maxBodyLength }}"
                                  data-counter-target="reply-counter-{{ $message->id }}"
                                  placeholder="{{ __('admin_messages.thread.reply_placeholder') }}"
                                  class="admin-reply-input w-full px-4 py-2.5 rounded-xl neu-pressed bg-ttu-cream border-0 focus:ring-2 focus:ring-ttu-red/30 outline-none resize-none"></textarea>

                        <div class="flex items-center justify-between gap-4 mt-2">
                            <span id="reply-counter-{{ $message->id }}" class="text-[11px] text-ttu-gray/70">
                                {{ __('admin_messages.thread.counter', ['count' => 0, 'max' => $maxBodyLength]) }}
                            </span>
                            <button type="submit" class="neu-icon-btn bg-ttu-red text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:!bg-ttu-red-dark">
                                {{ __('admin_messages.thread.reply_submit') }}
                            </button>
                        </div>
                    </form>

                </div>
            @empty
                <div class="rounded-[2rem] neu-raised-white p-10">
                    <p class="text-center text-sm text-ttu-gray">{{ __('admin_messages.empty') }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $messages->links() }}
        </div>

    </div>
</div>

<script>
    // عدّاد حروف لكل فورم رد — نفس سقف الخادم (Message::MAX_BODY_LENGTH)
    (function () {
        const counterTemplate = @json(__('admin_messages.thread.counter', ['count' => '__COUNT__', 'max' => $maxBodyLength]));

        document.querySelectorAll('.admin-reply-input').forEach(function (input) {
            const counter = document.getElementById(input.dataset.counterTarget);
            if (!counter) return;

            input.addEventListener('input', function () {
                counter.textContent = counterTemplate.replace('__COUNT__', input.value.length);
            });
        });
    })();
</script>

@endsection
