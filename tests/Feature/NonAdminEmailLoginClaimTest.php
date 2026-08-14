<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * الطلاب/الموظفون/الأطباء يسجّلون الدخول بالرقم الجامعي/الوظيفي فقط —
 * المدير وحده يستخدم البريد الإلكتروني. هذه الاختبارات تتحقق أن كل محتوى
 * يخاطب الأدوار غير الإدارية (الصفحة الرئيسية، صفحة الدخول بحسب الدور،
 * ومحتوى الشات بوت الثابت) لا يزال يذكر البريد كوسيلة دخول لها، بعد أن كان
 * محتوى الشات بوت تحديدًا يذكر ذلك خطأً.
 */
class NonAdminEmailLoginClaimTest extends TestCase
{
    public function test_home_page_role_cards_only_mention_email_for_the_admin_card(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('home.roles_section.student.login_via'));
        $response->assertSee(__('home.roles_section.staff.login_via'));
        $response->assertSee(__('home.roles_section.doctor.login_via'));
        $response->assertSee(__('home.roles_section.admin.login_via'));

        $this->assertStringNotContainsString('بريد', __('home.roles_section.student.login_via'));
        $this->assertStringNotContainsString('بريد', __('home.roles_section.staff.login_via'));
        $this->assertStringNotContainsString('بريد', __('home.roles_section.doctor.login_via'));
        $this->assertStringContainsString('البريد', __('home.roles_section.admin.login_via'));
    }

    public function test_login_page_field_label_mentions_email_only_for_admin_role(): void
    {
        foreach (['student', 'staff', 'doctor'] as $role) {
            $response = $this->get(route('login', ['role' => $role]));

            $response->assertOk();
            $response->assertSee(__('auth_forms.login.login_field_'.$role));
            $this->assertStringNotContainsString('بريد', __('auth_forms.login.login_field_'.$role));
        }

        $adminResponse = $this->get(route('login', ['role' => 'admin']));
        $adminResponse->assertOk();
        $this->assertStringContainsString('البريد', __('auth_forms.login.login_field_admin'));
    }

    public function test_chatbot_login_answers_do_not_claim_email_login_for_everyone(): void
    {
        // نفحص كل نصوص chatbot.php مباشرة (لا عبر route محدد — محتوى الشات
        // بوت الثابت يُبنى بالكامل من ملف الترجمة هذا عبر ChatbotFlow)
        foreach (['ar', 'en'] as $locale) {
            $chatbot = require base_path("lang/{$locale}/chatbot.php");
            $flat = json_encode($chatbot, JSON_UNESCAPED_UNICODE);

            // النمط القديم الخاطئ: "أو" متبوعًا بذكر البريد كخيار دخول بديل
            // للجميع. لا نفحص غياب كلمة "بريد" كليًا لأن الاستثناء الصحيح
            // للحساب الإداري ما زال يُذكر عمدًا ("الحساب الإداري وحده...")
            $this->assertStringNotContainsString('رقمك الجامعي أو الوظيفي — أو بالبريد', $flat);
            $this->assertStringNotContainsString('or the email on your account', $flat);
            $this->assertStringNotContainsString('or the email address registered on your account', $flat);

            // الاستثناء الصحيح موجود فعلًا (الحساب الإداري تحديدًا)
            $adminWord = $locale === 'ar' ? 'الحساب الإداري' : 'admin account';
            $this->assertStringContainsString($adminWord, $flat);
        }
    }
}
