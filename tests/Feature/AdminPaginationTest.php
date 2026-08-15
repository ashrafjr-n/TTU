<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->numerify('########')]);
    }

    public function test_pagination_renders_localized_arabic_text_and_not_raw_default_markup(): void
    {
        $admin = $this->admin();
        User::factory()->count(20)->create(['role' => 'student']);

        $response = $this->actingAs($admin)->get(route('admin.users'));

        $response->assertOk();

        // النصوص المترجمة يجب أن تظهر بدل النص الإنجليزي الافتراضي غير المترجم
        $response->assertSee('السابق');
        $response->assertSee('التالي');
        $response->assertSee('عرض 1 إلى 15 من أصل 20 نتيجة');
        $response->assertSee('التنقل بين الصفحات');

        // القالب المخصص (نيومورفيزم) يجب أن يُستخدم بدل قالب Laravel الافتراضي
        $response->assertSee('neu-icon-btn', false);

        // مفاتيح الترجمة الخام أو النص الإنجليزي الافتراضي لا يجب أن يظهرا بالعربية
        $response->assertDontSee('Pagination Navigation');
        $response->assertDontSeeText('Showing');
        $response->assertDontSee('pagination.previous');

        // قالب Laravel الافتراضي (Tailwind) يستخدم كلاسات border-gray الرمادية الخام
        $response->assertDontSee('border-gray-300', false);
    }

    public function test_pagination_renders_localized_english_text_when_locale_is_english(): void
    {
        $admin = $this->admin();
        User::factory()->count(20)->create(['role' => 'student']);

        $response = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.users'));

        $response->assertOk();
        $response->assertSee('Previous');
        $response->assertSee('Next');
        $response->assertSee('Showing 1 to 15 of 20 results');
    }
}
