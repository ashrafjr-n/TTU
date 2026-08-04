<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Medication;
use App\Models\UniversityRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->numerify('########')]);
    }

    public function test_admin_dashboard_switches_language(): void
    {
        $admin = $this->admin();

        $ar = $this->actingAs($admin)->get(route('admin.dashboard'));
        $ar->assertOk();
        $ar->assertSee('لوحة المدير', false);
        $ar->assertSee('الإحصائيات والتحليلات');
        $ar->assertSee('طالب مسجل');

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.dashboard'));
        $en->assertOk();
        $en->assertSee('Statistics &amp; Analytics', false);
        $en->assertSee('Registered students');
        $en->assertDontSee('الإحصائيات والتحليلات');
    }

    public function test_admin_users_page_switches_language(): void
    {
        $admin = $this->admin();
        User::factory()->create(['role' => 'student', 'identifier' => fake()->unique()->numerify('########')]);

        $ar = $this->actingAs($admin)->get(route('admin.users'));
        $ar->assertOk();
        $ar->assertSee('إدارة المستخدمين');
        $ar->assertSee('تعطيل');

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.users'));
        $en->assertOk();
        $en->assertSee('Manage Users');
        $en->assertSee('Deactivate');
    }

    public function test_admin_records_page_switches_language(): void
    {
        $admin = $this->admin();
        UniversityRecord::create(['identifier' => '12345678', 'type' => 'student', 'is_valid' => true]);

        $ar = $this->actingAs($admin)->get(route('admin.records'));
        $ar->assertOk();
        $ar->assertSee('سجلات الجامعة');

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.records'));
        $en->assertOk();
        $en->assertSee('University Records');
        $en->assertSee('Student');
    }

    public function test_admin_medications_page_switches_language(): void
    {
        $admin = $this->admin();
        Medication::create(['name' => 'Panadol', 'stock_quantity' => 5, 'low_stock_threshold' => 10]);

        $ar = $this->actingAs($admin)->get(route('admin.medications'));
        $ar->assertOk();
        $ar->assertSee('إدارة الأدوية');
        $ar->assertSee('⚠ مخزون منخفض');

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.medications'));
        $en->assertOk();
        $en->assertSee('Manage Medications');
        $en->assertSee('⚠ Low stock');
    }

    public function test_admin_attendance_page_switches_language(): void
    {
        $admin = $this->admin();

        $ar = $this->actingAs($admin)->get(route('admin.attendance'));
        $ar->assertOk();
        $ar->assertSee('حضور الأطباء');
        $ar->assertSee('لا يوجد أطباء مجدولون غدًا');

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.attendance'));
        $en->assertOk();
        $en->assertSee('Doctor Attendance');
        $en->assertSee('No doctors are scheduled for tomorrow');
    }

    public function test_admin_activity_log_page_switches_language_and_translates_entries(): void
    {
        $admin = $this->admin();
        ActivityLog::record($admin->id, 'doctor_created', 'activity_log.doctor_created', [
            'name' => 'Dr. Sami', 'email' => 'sami@ttu.edu.jo',
        ]);

        $ar = $this->actingAs($admin)->get(route('admin.activity-log'));
        $ar->assertOk();
        $ar->assertSee('سجل نشاط الإدارة');
        $ar->assertSee('إنشاء حساب طبيب: Dr. Sami');

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.activity-log'));
        $en->assertOk();
        $en->assertSee('Admin Activity Log');
        $en->assertSee('Created doctor account: Dr. Sami');
    }

    public function test_create_doctor_page_switches_language(): void
    {
        $admin = $this->admin();

        $ar = $this->actingAs($admin)->get(route('admin.doctors.create'));
        $ar->assertOk();
        $ar->assertSee('إضافة حساب طبيب جديد');

        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.doctors.create'));
        $en->assertOk();
        $en->assertSee('Add a New Doctor Account');
    }
}
