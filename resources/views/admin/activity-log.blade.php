@extends('layouts.main')

@section('title', __('admin_activity_log.page_title'))

@section('content')

<x-app-header />

<div class="min-h-[calc(100vh-80px)] bg-ttu-cream">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">

        @include('partials.admin-header')

        <h2 class="font-display text-2xl sm:text-3xl font-extrabold mb-8">{{ __('admin_activity_log.heading') }}</h2>

        @include('partials.admin-nav')

        <p class="text-sm text-ttu-gray mb-6">{{ __('admin_activity_log.subheading') }}</p>

        @include('admin.partials.activity-log-list', ['logs' => $logs, 'showActor' => true])

    </div>
</div>
@endsection
