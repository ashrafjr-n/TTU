<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'identifier' => 'admin-audit']);
    }

    public function test_toggling_a_users_status_flips_is_active(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.users.toggle', $user));
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)->post(route('admin.users.toggle', $user));
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_an_admin_account_cannot_be_disabled(): void
    {
        $admin = $this->admin();
        $otherAdmin = User::factory()->create(['role' => 'admin', 'identifier' => 'other-admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.users.toggle', $otherAdmin));

        $this->assertTrue($otherAdmin->fresh()->is_active);
    }
}
