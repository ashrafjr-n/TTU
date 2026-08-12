<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * نقطة النهاية الوحيدة لوضع "محادثة" في ويدجت الدعم. باقي الويدجت (الطبقات
 * الثابتة) لا يستدعي أي شيء من الخادم.
 *
 * ترجع 200 دائمًا مع نص جاهز للعرض — الخدمة نفسها تتكفّل بتحويل أي فشل إلى
 * الرسالة الثابتة المترجمة، كي لا يظهر للمستخدم خطأ خام أو واجهة معطوبة.
 */
class ChatbotController extends Controller
{
    public function message(Request $request, ChatbotService $chatbot): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        return response()->json($chatbot->reply($validated['message']));
    }
}
