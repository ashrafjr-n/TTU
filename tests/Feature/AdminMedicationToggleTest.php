<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تفعيل/تعطيل دواء يجب أن يُسجَّل في سجل النشاط — مثل كل إجراءات الإدارة
 * الأخرى على الأدوية (إضافة/تعديل/إضافة كمية)، وكان هذا الإجراء تحديدًا
 * الوحيد الذي يُغيّر حالة مؤثرة (يُخفي الدواء عن وصف الأطباء) دون أي أثر
 * بسجل النشاط.
 */
class AdminMedicationToggleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => fake()->unique()->safeEmail()]);
    }

    private function medication(bool $active = true): Medication
    {
        return Medication::create([
            'name_ar' => 'دواء تجريبي',
            'name_en' => 'Test Medication',
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
            'is_active' => $active,
        ]);
    }

    public function test_toggling_flips_is_active(): void
    {
        $medication = $this->medication(true);

        $this->actingAs($this->admin())->post(route('admin.medications.toggle', $medication));
        $this->assertFalse($medication->fresh()->is_active);

        $this->actingAs($this->admin())->post(route('admin.medications.toggle', $medication));
        $this->assertTrue($medication->fresh()->is_active);
    }

    public function test_deactivating_writes_an_activity_log_entry(): void
    {
        $admin = $this->admin();
        $medication = $this->medication(true);

        $this->actingAs($admin)->post(route('admin.medications.toggle', $medication));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'medication_deactivated',
        ]);
    }

    public function test_reactivating_writes_an_activity_log_entry(): void
    {
        $admin = $this->admin();
        $medication = $this->medication(false);

        $this->actingAs($admin)->post(route('admin.medications.toggle', $medication));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'medication_activated',
        ]);

        $log = ActivityLog::where('action', 'medication_activated')->first();
        $this->assertStringContainsString('دواء تجريبي', $log->renderedDescription());
    }
}
