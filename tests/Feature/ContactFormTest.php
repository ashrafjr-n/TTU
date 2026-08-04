<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Notifications\DoctorMessageReceived;
use App\Notifications\MessageReplyReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * فورم "تواصل": مقصور على الطالب/الموظف المسجّل دخوله، يختار أحد الأطباء
 * من قائمة ويرسله رسالة تظهر كإشعار عند الدكتور (مع اسم المرسل والنص)،
 * ويقدر الدكتور يرد عليها من نفس لوحة الإشعارات فيوصل الرد للمُرسِل الأصلي.
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
        $admin = User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->numerify('########')]);

        $response = $this->actingAs($admin)->get(route('contact'));

        $response->assertForbidden();
    }

    public function test_a_student_can_view_the_contact_page_with_the_doctor_list(): void
    {
        $doctor = $this->doctor();
        $student = $this->student();

        $response = $this->actingAs($student)->get(route('contact'));

        $response->assertOk();
        $response->assertSee($doctor->name);
    }

    public function test_a_student_can_send_a_message_to_a_doctor(): void
    {
        Notification::fake();

        $doctor = $this->doctor();
        $student = $this->student();

        $response = $this->actingAs($student)->post(route('contact.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'عندي استفسار عن مواعيد العيادة.',
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $student->id,
            'recipient_id' => $doctor->id,
            'body' => 'عندي استفسار عن مواعيد العيادة.',
        ]);

        Notification::assertSentTo($doctor, DoctorMessageReceived::class);
    }

    public function test_a_staff_member_can_send_a_message_to_a_doctor(): void
    {
        Notification::fake();

        $doctor = $this->doctor();
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('contact.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'سؤال بخصوص الدوام.',
        ])->assertSessionHas('success');

        Notification::assertSentTo($doctor, DoctorMessageReceived::class);
    }

    public function test_the_name_field_cannot_be_overridden_by_the_request(): void
    {
        Notification::fake();

        $doctor = $this->doctor();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'رسالة تجريبية.',
            'name' => 'اسم مزيف',
        ]);

        $message = Message::first();
        $this->assertSame($student->id, $message->sender_id);
        $this->assertSame($student->name, $message->sender->name);
    }

    public function test_doctor_id_is_required(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->student())->post(route('contact.store'), [
            'doctor_id' => '',
            'message' => 'رسالة بدون دكتور.',
        ]);

        $response->assertSessionHasErrors('doctor_id');
        Notification::assertNothingSent();
    }

    public function test_message_is_required(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->student())->post(route('contact.store'), [
            'doctor_id' => $this->doctor()->id,
            'message' => '',
        ]);

        $response->assertSessionHasErrors('message');
        Notification::assertNothingSent();
    }

    public function test_the_message_notification_shows_sender_name_and_body_to_the_doctor(): void
    {
        $doctor = $this->doctor();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'هل يوجد موعد فاضي اليوم؟',
        ]);

        $response = $this->actingAs($doctor)->get(route('dashboard.doctor'));

        $response->assertOk();
        $response->assertSee($student->name);
        $response->assertSee('هل يوجد موعد فاضي اليوم؟');
    }

    public function test_the_doctor_can_reply_and_the_sender_gets_notified(): void
    {
        $doctor = $this->doctor();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'هل يوجد موعد فاضي اليوم؟',
        ]);

        $message = Message::first();

        Notification::fake();

        $response = $this->actingAs($doctor)->postJson(route('messages.reply', $message), [
            'body' => 'نعم، تفضل الساعة 10.',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $doctor->id,
            'recipient_id' => $student->id,
            'body' => 'نعم، تفضل الساعة 10.',
            'parent_message_id' => $message->id,
        ]);

        Notification::assertSentTo($student, MessageReplyReceived::class);
    }

    public function test_the_reply_appears_in_the_original_senders_notifications(): void
    {
        $doctor = $this->doctor();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'هل يوجد موعد فاضي اليوم؟',
        ]);

        $message = Message::first();

        $this->actingAs($doctor)->postJson(route('messages.reply', $message), [
            'body' => 'نعم، تفضل الساعة 10.',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertSee($doctor->name);
        $response->assertSee('نعم، تفضل الساعة 10.');
    }

    public function test_a_doctor_cannot_reply_to_a_message_addressed_to_someone_else(): void
    {
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [
            'doctor_id' => $doctor->id,
            'message' => 'رسالة للدكتور الأول.',
        ]);

        $message = Message::first();

        $response = $this->actingAs($otherDoctor)->postJson(route('messages.reply', $message), [
            'body' => 'محاولة رد من دكتور آخر.',
        ]);

        $response->assertForbidden();
    }
}
