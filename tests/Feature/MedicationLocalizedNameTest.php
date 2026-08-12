<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اسم الدواء صار مخزَّنًا بعمودين (name_ar/name_en) بدل نص واحد ثابت —
 * يتحقق هذا الملف أن سمة Medication::name المشتقة تعرض الاسم الصحيح حسب
 * لغة الواجهة الحالية، وأن هذا ينعكس فعليًا بكل الأماكن التي تعرض اسم دواء
 * (صفحة "أدويتي"، لوحة الأدوية بالإدارة، فورم إضافة/تعديل دواء).
 */
class MedicationLocalizedNameTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->safeEmail()]);
    }

    public function test_name_attribute_follows_the_current_locale(): void
    {
        $medication = Medication::create([
            'name_ar' => 'بنادول',
            'name_en' => 'Panadol',
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
        ]);

        app()->setLocale('ar');
        $this->assertSame('بنادول', $medication->fresh()->name);

        app()->setLocale('en');
        $this->assertSame('Panadol', $medication->fresh()->name);
    }

    public function test_admin_medications_page_shows_the_name_in_the_active_locale(): void
    {
        // ملاحظة: فورم التعديل المخفي بأسفل كل دواء يعرض دائمًا القيمتين
        // الخام (name_ar/name_en) بصرف النظر عن اللغة الحالية — هذا مقصود
        // (المدير يحتاج يرى/يعدّل الاسمين معًا)، فلا نستخدم assertDontSee
        // على الصفحة كاملة هنا؛ بدل ذلك نتحقق من فقرة العرض الرئيسية تحديدًا
        // (بطاقة الدواء بالقائمة، لا فورم التعديل).
        $admin = $this->admin();
        Medication::create(['name_ar' => 'بنادول', 'name_en' => 'Panadol', 'stock_quantity' => 10, 'low_stock_threshold' => 5]);

        $ar = $this->actingAs($admin)->get(route('admin.medications'));
        $ar->assertOk();
        $ar->assertSee('<p class="text-sm font-bold text-ttu-black">بنادول</p>', false);

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.medications'));
        $en->assertOk();
        $en->assertSee('<p class="text-sm font-bold text-ttu-black">Panadol</p>', false);
    }

    public function test_admin_can_create_a_medication_with_both_names(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.medications.store'), [
            'name_ar' => 'سينوتاب',
            'name_en' => 'Sinutab',
            'stock_quantity' => 50,
            'unit' => 'شريط',
            'low_stock_threshold' => 10,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('medications', [
            'name_ar' => 'سينوتاب',
            'name_en' => 'Sinutab',
        ]);
    }

    public function test_creating_a_medication_requires_both_names(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.medications.store'), [
            'name_ar' => 'سينوتاب',
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
        ]);

        $response->assertSessionHasErrors('name_en');
        $this->assertDatabaseMissing('medications', ['name_ar' => 'سينوتاب']);
    }
}
