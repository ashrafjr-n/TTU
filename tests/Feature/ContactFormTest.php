<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Notifications\AdminMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * فورم "تواصل": مقصور على الطالب/الموظف المسجّل دخوله، ولا اختيار للمستقبِل
 * فيه — كل رسالة تذهب إلى إدارة العيادة وتظهر كإشعار عند المديرين، ويردّ
 * عليها المدير من صندوق الوارد بلوحته (راجع AdminMessagesTest).
 */
class ContactFormTest extends TestCase
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

    public function test_a_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('contact'));

        $response->assertRedirect(route('login'));
    }

    public function test_a_doctor_cannot_access_the_contact_page(): void
    {
        $response = $this->actingAs($this->doctor())->get(route('contact'));

        $response->assertForbidden();
    }

    public function test_an_admin_cannot_access_the_contact_page(): void
    {
        $response = $this->actingAs($this->admin())->get(route('contact'));

        $response->assertForbidden();
    }

    /**
     * الصفحة تقول صراحةً إلى من تذهب الرسالة، ولم تعد تعرض أي قائمة أطباء
     * يُختار منها المستقبِل.
     */
    public function test_the_contact_page_states_the_admin_is_the_recipient_and_offers_no_doctor_choice(): void
    {
        $doctor = $this->doctor();

        $response = $this->actingAs($this->student())->get(route('contact'));

        $response->assertOk();
        $response->assertSee(__('contact.form.recipient_notice'));
        $response->assertSee(__('contact.form.recipient_value'));
        $response->assertDontSee($doctor->name);
        $response->assertDontSee('name="doctor_id"', false);
    }

    public function test_a_student_can_send_a_message_to_the_admin(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $student = $this->student();

        $response = $this->actingAs($student)->post(route('contact.store'), [
            'message' => 'عندي استفسار عن مواعيد العيادة.',
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $student->id,
            'recipient_id' => $admin->id,
            'body' => 'عندي استفسار عن مواعيد العيادة.',
            'parent_message_id' => null,
        ]);

        Notification::assertSentTo($admin, AdminMessageReceived::class);
    }

    public function test_a_staff_member_can_send_a_message_to_the_admin(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('contact.store'), [
            'message' => 'سؤال بخصوص الدوام.',
        ])->assertSessionHas('success');

        Notification::assertSentTo($admin, AdminMessageReceived::class);
    }

    /**
     * الرسالة تُخزَّن باسم أول مدير، لكن كل المديرين يُشعَرون بها — فأول من
     * يفتح اللوحة يقدر يرد.
     */
    public function test_every_admin_is_notified_even_though_one_is_stored_as_the_recipient(): void
    {
        Notification::fake();

        $firstAdmin = $this->admin();
        $secondAdmin = $this->admin();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'message' => 'رسالة لكل الإدارة.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('messages', ['recipient_id' => $firstAdmin->id]);

        Notification::assertSentTo($firstAdmin, AdminMessageReceived::class);
        Notification::assertSentTo($secondAdmin, AdminMessageReceived::class);
    }

    public function test_the_name_field_cannot_be_overridden_by_the_request(): void
    {
        Notification::fake();

        $this->admin();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'message' => 'رسالة تجريبية.',
            'name' => 'اسم مزيف',
        ]);

        $message = Message::first();
        $this->assertSame($student->id, $message->sender_id);
        $this->assertSame($student->name, $message->sender->name);
    }

    /**
     * لا يمكن توجيه الرسالة لغير الإدارة بحقن معرّف بالطلب — الحقل غير
     * موجود بالفورم أصلًا ولا يُقرأ من الطلب.
     */
    public function test_a_planted_recipient_id_is_ignored(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $doctor = $this->doctor();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'message' => 'محاولة توجيه لطبيب.',
            'doctor_id' => $doctor->id,
            'recipient_id' => $doctor->id,
        ])->assertSessionHas('success');

        $this->assertSame($admin->id, Message::first()->recipient_id);
    }

    public function test_message_is_required(): void
    {
        Notification::fake();

        $this->admin();

        $response = $this->actingAs($this->student())->post(route('contact.store'), [
            'message' => '',
        ]);

        $response->assertSessionHasErrors('message');
        Notification::assertNothingSent();
    }

    public function test_a_message_at_the_length_cap_is_accepted(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $body = str_repeat('ا', Message::MAX_BODY_LENGTH);

        $response = $this->actingAs($this->student())->post(route('contact.store'), [
            'message' => $body,
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame($body, Message::first()->body);

        Notification::assertSentTo($admin, AdminMessageReceived::class);
    }

    public function test_a_message_longer_than_the_cap_is_rejected_with_a_localized_error(): void
    {
        Notification::fake();

        $this->admin();

        $response = $this->actingAs($this->student())->post(route('contact.store'), [
            'message' => str_repeat('ا', Message::MAX_BODY_LENGTH + 1),
        ]);

        $response->assertSessionHasErrors([
            'message' => __('contact.errors.message_max', ['max' => Message::MAX_BODY_LENGTH]),
        ]);

        $this->assertDatabaseCount('messages', 0);
        Notification::assertNothingSent();
    }

    /**
     * السقف نفسه معلن بالواجهة (maxlength + عدّاد مرئي) لا بالخادم وحده.
     */
    public function test_the_form_advertises_the_length_cap_to_the_browser(): void
    {
        $response = $this->actingAs($this->student())->get(route('contact'));

        $response->assertOk();
        $response->assertSee('maxlength="'.Message::MAX_BODY_LENGTH.'"', false);
        $response->assertSee('id="contact-message-counter"', false);
    }

    public function test_the_message_notification_shows_sender_name_and_body_to_the_admin(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'message' => 'هل يوجد موعد فاضي اليوم؟',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('notifications.admin_message.title', ['name' => $student->name]));
        $response->assertSee('هل يوجد موعد فاضي اليوم؟');
    }

    public function test_sending_fails_gracefully_when_no_admin_account_exists(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->student())->post(route('contact.store'), [
            'message' => 'رسالة بلا مدير.',
        ]);

        $response->assertStatus(503);
        $this->assertDatabaseCount('messages', 0);
        Notification::assertNothingSent();
    }
}
