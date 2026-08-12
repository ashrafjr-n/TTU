#!/bin/sh
set -e

php artisan migrate --force
php artisan db:seed --force

# جدولة Laravel (attendance:auto-checkout يوميًا 4 عصرًا،
# notifications:send-reminders كل 5 دقائق — راجع bootstrap/app.php) لا
# تعمل من تلقاء نفسها؛ تحتاج شيئًا يستدعي schedule:run كل دقيقة. الحاوية
# هنا عملية واحدة تشغّل php artisan serve (لا php-fpm خلف كرون نظام أو
# عامل منفصل)، فأبسط وأوثق حل بلا خدمة/حاوية إضافية هو تشغيل
# schedule:work بالخلفية طوال عمر الحاوية — ينام بين كل دقيقة والتالية
# وينفّذ schedule:run داخليًا، فيغطي كلا الأمرين المجدولين تلقائيًا. البديل
# (كرون خارجي — مثلًا Render Cron Job منفصل يستدعي schedule:run كل دقيقة)
# كان يحتاج خدمة إضافية بفوترة وإعداد منفصلين لتزامن بسيط كهذا، فلا يستحق
# التعقيد الإضافي بحجم هذا التطبيق.
php artisan schedule:work &
SCHEDULER_PID=$!

php artisan serve --host=0.0.0.0 --port="${PORT}" &
SERVER_PID=$!

# لا نستخدم exec هنا عمدًا: البقاء كعملية shell (PID 1) بدل استبدالها
# بخادم serve هو ما يتيح لنا التقاط إشارة الإيقاف (SIGTERM من المضيف عند
# إعادة النشر/الإيقاف) وإنهاء عملية الجدولة الخلفية بشكل نظيف معه، بدل
# تركها "يتيمة" تعمل بلا داعٍ بعد توقف الخادم الرئيسي.
trap 'kill "$SCHEDULER_PID" "$SERVER_PID" 2>/dev/null' TERM INT

wait "$SERVER_PID"
