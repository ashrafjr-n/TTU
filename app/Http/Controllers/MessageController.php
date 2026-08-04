<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Notifications\MessageReplyReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * رد الدكتور على رسالة وصلته عبر فورم "تواصل" — يُستدعى من لوحة
     * الإشعارات مباشرة (fetch)، فيرجع JSON بدل صفحة كاملة.
     */
    public function reply(Request $request, Message $message): JsonResponse
    {
        abort_unless($message->recipient_id === $request->user()->id, 403);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $reply = Message::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $message->sender_id,
            'body' => $validated['body'],
            'parent_message_id' => $message->id,
        ]);

        $reply->recipient->notify(new MessageReplyReceived($reply));

        return response()->json(['success' => true]);
    }
}
