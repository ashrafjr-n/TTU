<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markRead(DatabaseNotification $notification)
    {
        abort_unless(
            $notification->notifiable_type === \App\Models\User::class && $notification->notifiable_id === Auth::id(),
            403
        );

        $notification->markAsRead();

        return response()->json([
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['unread_count' => 0]);
    }
}
