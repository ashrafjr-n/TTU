<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markRead(DatabaseNotification $notification)
    {
        abort_unless(
            $notification->notifiable_type === User::class && $notification->notifiable_id === Auth::id(),
            403
        );

        $notification->markAsRead();

        return response()->json([
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['unread_count' => 0]);
    }
}
