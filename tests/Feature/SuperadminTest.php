<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_owner_can_open_account_management(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('Kelola Akun')
            ->assertSee($owner->email);
    }

    public function test_non_owner_cannot_open_superadmin_pages(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($manager)->get(route('admin.accounts.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.activity.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.accounts.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.activity.index'))->assertForbidden();
    }

    public function test_owner_can_lock_and_unlock_another_account(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($owner)
            ->post(route('admin.accounts.lock', $cashier))
            ->assertRedirect();

        $this->assertFalse($cashier->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'account.locked',
            'subject_id' => $cashier->id,
            'user_id' => $owner->id,
        ]);
        $this->assertSame(1, AuditLog::query()->where('event', 'account.locked')->count());
        $this->assertDatabaseMissing('audit_logs', ['event' => 'http.post']);

        $this->actingAs($owner)
            ->post(route('admin.accounts.unlock', $cashier))
            ->assertRedirect();

        $this->assertTrue($cashier->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'account.unlocked',
            'subject_id' => $cashier->id,
        ]);
    }

    public function test_owner_cannot_lock_own_account(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('admin.accounts.lock', $owner))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_locked_account_is_logged_out_of_admin_area(): void
    {
        $locked = User::factory()->cashier()->create(['is_active' => false]);

        $this->actingAs($locked)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_owner_can_change_email_and_role(): void
    {
        $owner = User::factory()->owner()->create();
        $employee = User::factory()->employee()->create();

        $this->actingAs($owner)
            ->put(route('admin.accounts.update', $employee), [
                'name' => 'Nama Baru',
                'employee_code' => $employee->employee_code,
                'email' => 'email.baru@stark-garage.local',
                'phone' => '08123456789',
                'role' => 'cashier',
            ])
            ->assertRedirect(route('admin.accounts.edit', $employee));

        $employee->refresh();

        $this->assertSame('email.baru@stark-garage.local', $employee->email);
        $this->assertSame('cashier', $employee->role->value);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'account.updated',
            'subject_id' => $employee->id,
        ]);
    }

    public function test_owner_can_reset_password_without_old_password(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($owner)
            ->post(route('admin.accounts.password', $cashier), [
                'password' => 'rahasia-baru-123',
                'password_confirmation' => 'rahasia-baru-123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('rahasia-baru-123', $cashier->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'account.password_reset',
            'subject_id' => $cashier->id,
        ]);
    }

    public function test_owner_can_reset_face_data(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create([
            'face_embedding' => array_fill(0, 128, 0.1),
            'face_registered_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('admin.accounts.reset-face', $cashier))
            ->assertRedirect();

        $cashier->refresh();

        $this->assertNull($cashier->face_embedding);
        $this->assertNull($cashier->face_registered_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'account.face_reset',
            'subject_id' => $cashier->id,
        ]);
    }

    public function test_owner_can_force_logout_and_revoke_tokens(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $oldRememberToken = $cashier->remember_token;

        DB::table('sessions')->insert([
            'id' => 'session-cashier-1',
            'user_id' => $cashier->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $cashier->createToken('test-token');

        $this->actingAs($owner)
            ->post(route('admin.accounts.force-logout', $cashier))
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['user_id' => $cashier->id]);
        $this->assertSame(0, $cashier->tokens()->count());
        $this->assertNotSame($oldRememberToken, $cashier->fresh()->remember_token);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'account.force_logout',
            'subject_id' => $cashier->id,
        ]);
    }

    public function test_manager_cannot_edit_owner_or_assign_owner_role(): void
    {
        $manager = User::factory()->manager()->create();
        $owner = User::factory()->owner()->create();
        $employee = User::factory()->employee()->create();

        $this->actingAs($manager)
            ->get(route('admin.employees.edit', $owner))
            ->assertForbidden();

        $this->actingAs($manager)
            ->put(route('admin.employees.update', $employee), [
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'email' => $employee->email,
                'role' => 'owner',
                'salary_type' => 'monthly',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('employee', $employee->fresh()->role->value);
    }

    public function test_activity_log_is_owner_only_and_lists_entries(): void
    {
        $owner = User::factory()->owner()->create();
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create();

        AuditLog::create([
            'user_id' => $cashier->id,
            'event' => 'account.locked',
            'description' => 'Akun uji dikunci',
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('Log Aktivitas')
            ->assertSee('Akun uji dikunci')
            ->assertSee($cashier->name);

        $this->actingAs($manager)
            ->get(route('admin.activity.index'))
            ->assertForbidden();
    }

    public function test_login_logout_and_generic_actions_are_logged(): void
    {
        $owner = User::factory()->owner()->create();

        $this->post(route('login'), [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($owner);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login',
            'user_id' => $owner->id,
        ]);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.logout',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->post(route('admin.attendance.absent'), [
                'date' => today()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'http.post',
            'user_id' => $owner->id,
        ]);
    }

    public function test_superadmin_navigation_only_appears_for_owner(): void
    {
        $owner = User::factory()->owner()->create();
        $manager = User::factory()->manager()->create();

        $this->actingAs($owner)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('Superadmin')
            ->assertSee('Log Aktivitas');

        $this->actingAs($manager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Kelola Akun');
    }

    public function test_managerial_pages_are_not_accessible_to_staff(): void
    {
        $cashier = User::factory()->cashier()->create();
        $employee = User::factory()->employee()->create();

        $this->actingAs($cashier)->get(route('admin.payroll.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_api_token_of_locked_account_is_revoked(): void
    {
        $cashier = User::factory()->cashier()->create();
        $token = $cashier->createToken('device')->plainTextToken;

        $cashier->update(['is_active' => false]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertForbidden();

        $this->assertSame(0, $cashier->tokens()->count());
    }
}
