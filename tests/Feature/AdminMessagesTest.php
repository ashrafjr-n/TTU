<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Notifications\ClinicReplyReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * صندوق وارد رسائل "تواصل" بلوحة الإدارة: عرض الرسائل الواردة والرد عليها،
 * وما يصل صاحب الرسالة من إشعار بالرد.
 *
 * الفرق الجوهري عن بقية الإشعارات مغطًّى هنا صراحةً: إشعار الرد لا يحمل نص
 * الرد لا في بياناته ولا في القائمة المنسدلة — النص لا يظهر إلا داخل لوحة
 * الرسالة التي تُفتح بالضغط عليه.
 */
class AdminMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->numerify('########')]);
    }

    private function sendMessage(User $sender, string $body = 'هل يوجد موعد فاضي اليوم؟'): Message
    {
        $this->actingAs($sender)->post(route('contact.store'), ['message' => $body])
            ->assertSessionHas('success');

        return Message::where('sender_id', $sender->id)->latest('id')->firstOrFail();
    }

    // ------------------------------------------------------------------
    // الوصول
    // ------------------------------------------------------------------

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.messages'))->assertRedirect(route('login'));
    }

    public function test_non_admins_cannot_open_the_inbox(): void
    {
        foreach ([$this->student(), $this->staff(), $this->doctor()] as $user) {
            $this->actingAs($user)->get(route('admin.messages'))->assertForbidden();
        }
    }

    public function test_the_inbox_is_linked_from_the_admin_navigation(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.messages'), false);
        $response->assertSee(__('admin_common.nav.messages'));
    }

    // ------------------------------------------------------------------
    // العرض
    // ------------------------------------------------------------------

    public function test_the_inbox_lists_incoming_messages_with_sender_and_body(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $this->sendMessage($student, 'رسالة الطالب للإدارة.');

        $response = $this->actingAs($admin)->get(route('admin.messages'));

        $response->assertOk();
        $response->assertSee($student->name);
        $response->assertSee($student->identifier);
        $response->assertSee('رسالة الطالب للإدارة.');
        $response->assertSee(__('admin_messages.thread.reply_submit'));
    }

    public function test_the_inbox_is_empty_when_nothing_was_sent(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.messages'));

        $response->assertOk();
        $response->assertSee(__('admin_messages.empty'));
    }

    /**
     * الرسائل تُخزَّن باسم أول مدير — والصندوق يعرضها لأي مدير آخر أيضًا،
     * وإلا صار الوارد حكرًا على حساب واحد.
     */
    public function test_a_second_admin_sees_the_same_inbox(): void
    {
        $this->admin();
        $secondAdmin = $this->admin();
        $this->sendMessage($this->student(), 'رسالة يراها كل المديرين.');

        $response = $this->actingAs($secondAdmin)->get(route('admin.messages'));

        $response->assertOk();
        $response->assertSee('رسالة يراها كل المديرين.');
    }

    public function test_replies_are_shown_under_their_message_not_as_separate_rows(): void
    {
        $admin = $this->admin();
        $message = $this->sendMessage($this->student(), 'الرسالة الأصلية.');

        $this->actingAs($admin)->post(route('admin.messages.reply', $message), [
            'body' => 'رد الإدارة على الرسالة.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.messages'));

        $response->assertOk();
        $response->assertSee('الرسالة الأصلية.');
        $response->assertSee('رد الإدارة على الرسالة.');
        $response->assertSee(__('admin_messages.thread.reply_badge'));
        // الرد نفسه ليس صفًا مستقلًا بالقائمة (الجذور فقط تُسرد)
        $response->assertSeeInOrder(['الرسالة الأصلية.', __('admin_messages.thread.replies_heading'), 'رد الإدارة على الرسالة.']);
    }

    // ------------------------------------------------------------------
    // الرد
    // ------------------------------------------------------------------

    public function test_the_admin_can_reply_and_the_sender_gets_notified(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $message = $this->sendMessage($student);

        Notification::fake();

        $response = $this->actingAs($admin)->post(route('admin.messages.reply', $message), [
            'body' => 'نعم، تفضل الساعة 10.',
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'recipient_id' => $student->id,
            'body' => 'نعم، تفضل الساعة 10.',
            'parent_message_id' => $message->id,
        ]);

        Notification::assertSentTo($student, ClinicReplyReceived::class);
    }

    public function test_a_staff_sender_also_gets_notified_of_the_reply(): void
    {
        $admin = $this->admin();
        $staff = $this->staff();
        $message = $this->sendMessage($staff, 'سؤال بخصوص الدوام.');

        Notification::fake();

        $this->actingAs($admin)->post(route('admin.messages.reply', $message), [
            'body' => 'الدوام من 8 إلى 4.',
        ])->assertSessionHas('success');

        Notification::assertSentTo($staff, ClinicReplyReceived::class);
    }

    public function test_a_non_admin_cannot_reply(): void
    {
        $this->admin();
        $student = $this->student();
        $message = $this->sendMessage($student);

        foreach ([$student, $this->staff(), $this->doctor()] as $user) {
            $this->actingAs($user)
                ->post(route('admin.messages.reply', $message), ['body' => 'محاولة رد.'])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('messages', 1);
    }

    public function test_a_reply_is_required_and_capped_at_the_same_length(): void
    {
        $admin = $this->admin();
        $message = $this->sendMessage($this->student());

        $this->actingAs($admin)
            ->post(route('admin.messages.reply', $message), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($admin)
            ->post(route('admin.messages.reply', $message), ['body' => str_repeat('ا', Message::MAX_BODY_LENGTH + 1)])
            ->assertSessionHasErrors([
                'body' => __('admin_messages.errors.body_max', ['max' => Message::MAX_BODY_LENGTH]),
            ]);

        // لا رد أُنشئ — الرسالة الأصلية وحدها بالجدول
        $this->assertDatabaseCount('messages', 1);
    }

    // ------------------------------------------------------------------
    // إشعار الرد: بلا نص، ولوحة تُفتح بالصفحة
    // ------------------------------------------------------------------

    /**
     * أهم فرق عن الإشعارات الأخرى: بيانات الإشعار المخزَّنة لا تحتوي نص
     * الرد إطلاقًا — لا كمفتاح body ولا ضمن أي حقل آخر.
     */
    public function test_the_reply_notification_payload_never_carries_the_reply_text(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $message = $this->sendMessage($student);

        $this->actingAs($admin)->post(route('admin.messages.reply', $message), [
            'body' => 'نص سري لا يجوز أن يظهر في قائمة الإشعارات.',
        ]);

        $data = $student->notifications()->latest()->first()->data;

        $this->assertSame('clinic_reply', $data['type']);
        $this->assertStringNotContainsString('نص سري لا يجوز أن يظهر في قائمة الإشعارات.', json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->assertArrayNotHasKey('body', $data);
    }

    /**
     * وفي القائمة المنسدلة نفسها: عنوان عام ("لديك رد من إدارة العيادة")
     * ومتن عام، بلا اسم مُرسِل ولا نص رد.
     */
    public function test_the_dropdown_shows_only_a_generic_reply_notice(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $message = $this->sendMessage($student);

        $this->actingAs($admin)->post(route('admin.messages.reply', $message), [
            'body' => 'رد لا يظهر بالقائمة.',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertSee(__('notifications.clinic_reply.title'));
        $response->assertSee(__('notifications.clinic_reply.body'));

        // النص لا يُطبع كمتن للإشعار — وجوده الوحيد بالصفحة هو سمة
        // data-reply-body التي تقرأها اللوحة عند فتحها
        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, 'رد لا يظهر بالقائمة.'));
        $this->assertStringContainsString('data-reply-body="رد لا يظهر بالقائمة."', $content);
    }

    /**
     * الضغط على الإشعار يفتح لوحة داخل الصفحة (لا انتقال): اللوحة مطبوعة
     * بالصفحة، والعنصر معلَّم بـdata-panel، ولا رابط انتقال له.
     */
    public function test_the_reply_notification_opens_an_in_page_panel_with_the_thread_context(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $message = $this->sendMessage($student, 'سؤالي الأصلي للإدارة.');

        $this->actingAs($admin)->post(route('admin.messages.reply', $message), [
            'body' => 'جواب الإدارة الكامل.',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertSee('id="messagePanelOverlay"', false);
        $response->assertSee(__('message_panel.heading'));
        $response->assertSee('data-panel="1"', false);
        $response->assertSee('data-url=""', false);
        // سياق المحادثة: الرد + الرسالة الأصلية
        $response->assertSee('data-reply-body="جواب الإدارة الكامل."', false);
        $response->assertSee('data-original-body="سؤالي الأصلي للإدارة."', false);
        $response->assertSee('data-reply-sender="'.$admin->name.'"', false);
    }

    /**
     * وداخل اللوحة زر "رد" ينقل لصفحة "تواصل" — لا رد من داخلها.
     */
    public function test_the_panel_reply_button_links_to_the_contact_page(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $message = $this->sendMessage($student);

        $this->actingAs($admin)->post(route('admin.messages.reply', $message), ['body' => 'رد.']);

        $response = $this->actingAs($student)->get(route('dashboard.student'));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('contact'), '/').'"[^>]*>.*?'.preg_quote(__('message_panel.reply_button'), '/').'/su',
            $response->getContent()
        );

        // ولا فورم رد داخل اللوحة
        $response->assertDontSee('id="messagePanelReplyInput"', false);
    }

    public function test_the_panel_renders_in_both_languages(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $message = $this->sendMessage($student);

        $this->actingAs($admin)->post(route('admin.messages.reply', $message), ['body' => 'رد ثنائي اللغة.']);

        $arabic = $this->actingAs($student)->withSession(['locale' => 'ar'])->get(route('dashboard.student'));
        $arabic->assertOk();
        $arabic->assertSee('لديك رد من إدارة العيادة');

        $english = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('dashboard.student'));
        $english->assertOk();
        $english->assertSee('You have a reply from the clinic administration');
        $english->assertSee('Clinic administration reply');
        $english->assertDontSee('لديك رد من إدارة العيادة');
    }

    /**
     * اللوحة تقرأ نص الرد من جدول الرسائل لا من بيانات الإشعار، فلا بد أن
     * يبقى ذلك مقصورًا على رسائل صاحب الحساب نفسه.
     */
    public function test_a_users_panel_never_exposes_another_users_message(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $otherStudent = $this->student();

        $message = $this->sendMessage($otherStudent, 'رسالة طالب آخر.');
        $this->actingAs($admin)->post(route('admin.messages.reply', $message), ['body' => 'رد خاص بطالب آخر.']);

        // إشعار مزروع على حساب طالب غير معنيّ، يشير لرد ليس له
        $reply = Message::latest('id')->first();
        $student->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => ClinicReplyReceived::class,
            'data' => [
                'type' => 'clinic_reply',
                'title_key' => 'notifications.clinic_reply.title',
                'body_key' => 'notifications.clinic_reply.body',
                'url' => null,
                'message_id' => $reply->id,
            ],
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertDontSee('رد خاص بطالب آخر.');
        $response->assertDontSee('رسالة طالب آخر.');
    }
}
