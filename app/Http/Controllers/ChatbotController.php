<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        // تحقق يدوي بدل $request->validate() عن قصد: إعدادات الاستثناءات
        // بالتطبيق (shouldRenderJsonWhen على api/* فقط) تحوّل فشل التحقق إلى
        // إعادة توجيه 302 لأي مسار خارج api، وهذا المسار يُستدعى بـfetch
        // ويحتاج ردًا JSON دائمًا.
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json($chatbot->reply((string) $validator->validated()['message']));
    }
}
