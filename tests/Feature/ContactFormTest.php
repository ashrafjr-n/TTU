<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ContactFormSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * فورم "تواصل معنا": الصفحة عامة (بدون تسجيل دخول)، والإرسال يُنشئ إشعارًا
 * لكل حسابات المدير — محاكاة داخلية بلا بريد فعلي.
 */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_page_is_publicly_accessible(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('تواصل معنا');
        $response->assertSee('XXX-XXXXXXX');
        $response->assertSee('clinic@xxx.edu.jo');
        $response->assertSee('8 صباحًا حتى 4 عصرًا');
    }

    public function test_a_guest_can_submit_the_contact_form(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'identifier' => 'admin-contact']);

        $response = $this->post(route('contact.store'), [
            'name' => 'زائر مهتم',
            'message' => 'عندي استفسار عن مواعيد العيادة.',
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();

        Notification::assertSentTo($admin, ContactFormSubmitted::class);
    }

    public function test_the_notification_carries_the_senders_name_and_message(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'identifier' => 'admin-contact-2']);

        $this->post(route('contact.store'), [
            'name' => 'أحمد',
            'message' => 'شكرًا على الخدمة الممتازة.',
        ]);

        Notification::assertSentTo($admin, function (ContactFormSubmitted $notification) use ($admin) {
            $data = $notification->toDatabase($admin);

            return $data['type'] === 'contact_message'
                && str_contains($data['body'], 'أحمد')
                && str_contains($data['body'], 'شكرًا على الخدمة الممتازة.');
        });
    }

    public function test_all_admin_accounts_receive_the_notification(): void
    {
        Notification::fake();
        $admin1 = User::factory()->create(['role' => 'admin', 'identifier' => 'admin-a']);
        $admin2 = User::factory()->create(['role' => 'admin', 'identifier' => 'admin-b']);
        $student = User::factory()->create(['role' => 'student', 'identifier' => '20219999']);

        $this->post(route('contact.store'), [
            'name' => 'موظف',
            'message' => 'رسالة تجريبية.',
        ]);

        Notification::assertSentTo($admin1, ContactFormSubmitted::class);
        Notification::assertSentTo($admin2, ContactFormSubmitted::class);
        Notification::assertNotSentTo($student, ContactFormSubmitted::class);
    }

    public function test_the_message_actually_appears_in_the_admins_notification_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'identifier' => 'admin-contact-3']);

        $this->post(route('contact.store'), [
            'name' => 'سارة',
            'message' => 'هل يوجد موعد فاضي اليوم؟',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('رسالة تواصل جديدة');
        $response->assertSee('سارة');
    }

    public function test_name_is_required(): void
    {
        Notification::fake();

        $response = $this->post(route('contact.store'), [
            'name' => '',
            'message' => 'رسالة بدون اسم.',
        ]);

        $response->assertSessionHasErrors('name');
        Notification::assertNothingSent();
    }

    public function test_message_is_required(): void
    {
        Notification::fake();

        $response = $this->post(route('contact.store'), [
            'name' => 'مستخدم',
            'message' => '',
        ]);

        $response->assertSessionHasErrors('message');
        Notification::assertNothingSent();
    }

    public function test_a_logged_in_user_can_also_submit_the_form(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'identifier' => 'admin-contact-4']);
        $student = User::factory()->create(['role' => 'student', 'identifier' => '20218888']);

        $response = $this->actingAs($student)->post(route('contact.store'), [
            'name' => $student->name,
            'message' => 'سؤال من طالب مسجّل دخول.',
        ]);

        $response->assertSessionHas('success');
        Notification::assertSentTo($admin, ContactFormSubmitted::class);
    }
}
