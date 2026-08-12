# OpenRouter — ملاحظات التكامل

هذا الملف يحلّ محل `test.py`، وهو سكربت تجريبي (spike) كان بجذر المشروع
يستدعي OpenRouter عبر مكتبة `openai` في بايثون **ومفتاح الـAPI مكتوب داخله
نصًّا صريحًا**. حُذف الملف: التكامل الحقيقي صار داخل Laravel، والمفتاح انتقل
إلى `.env` (غير متتبَّع في git) ويُقرأ عبر `config/services.php` فقط.

> `test.py` كان مُستثنى في `.gitignore` ولم يُرفع للمستودع في أي commit
> (`git log --all -- test.py` فارغ)، فلم يتسرّب المفتاح علنًا. مع ذلك يُنصح
> بتدوير (rotate) المفتاح من لوحة OpenRouter لأنه بقي مدة بملف نصي عادي.

## أين صار كل شيء

| قبل (`test.py`) | بعد |
| --- | --- |
| المفتاح مكتوب بالكود | `OPENROUTER_API_KEY` في `.env`، ونموذج فارغ في `.env.example` |
| `base_url` ثابت | `config('services.openrouter.base_url')` |
| `model` ثابت (`cohere/north-mini-code:free` — نموذج أكواد، غير مناسب للدعم) | `config('services.openrouter.model')`، الافتراضي نموذج محادثة مجاني |
| استدعاء مباشر بلا حدود | `App\Services\ChatbotService` — سقف يومي + مهلة زمنية + fallback |

## متغيّرات البيئة

```env
OPENROUTER_API_KEY=            # اتركه فارغًا لتعطيل المحادثة المباشرة
OPENROUTER_MODEL=meta-llama/llama-3.3-70b-instruct:free
OPENROUTER_TIMEOUT=12          # ثوانٍ، مهلة الطلب الواحد
CHATBOT_DAILY_LIMIT=40         # سقف الطلبات اليومي الصارم
```

المفتاح فارغًا ليس خطأً: ويدجت الدعم يعمل بكل طبقاته الثابتة (الأسئلة الجاهزة)
دون أي اتصال بالشبكة، ووضع "محادثة" وحده يُظهر رسالة "غير متاحة حاليًا".

## نقاط الاستدعاء

- `App\Services\ChatbotService` — بناء الـsystem prompt، الاستدعاء عبر
  `Http::withToken()`، حجز حصة من العدّاد اليومي، وكل مسارات الـfallback.
- `App\Models\ChatbotUsage` — عدّاد الطلبات، صف واحد لكل تاريخ (جدول
  `chatbot_usage`)، فيتصفّر تلقائيًا بتغيّر اليوم دون أي مهمة مجدولة.
- `App\Http\Controllers\ChatbotController` — نقطة النهاية
  `POST /chatbot/message` (محدودة بـ`throttle`).
