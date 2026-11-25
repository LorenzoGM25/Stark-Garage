<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeFaceOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function embedding(float $value = 0.1): array
    {
        return array_fill(0, 128, $value);
    }

    private function createEmployeePayload(): array
    {
        return [
            'name' => 'Karyawan Onboarding',
            'employee_code' => 'STG-991',
            'email' => 'onboarding@stark-garage.local',
            'phone' => '081200009991',
            'role' => 'employee',
            'base_salary' => 2500000,
            'salary_type' => 'monthly',
            'hourly_overtime_rate' => 20000,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
            'is_active' => 1,
        ];
    }

    public function test_creating_employee_redirects_to_mandatory_face_registration(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)
            ->post(route('admin.employees.store'), $this->createEmployeePayload());

        $employee = User::query()->where('email', 'onboarding@stark-garage.local')->firstOrFail();

        $response->assertRedirect(route('admin.attendance.face', [
            'user_id' => $employee->id,
            'mode' => 'register',
            'onboarding' => 1,
        ]));

        $this->assertTrue($employee->pending_face_registration);
        $this->assertTrue($employee->needsFaceRegistration());
        $this->assertFalse($employee->hasFace());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'employee.created',
            'subject_id' => $employee->id,
        ]);
    }

    public function test_onboarding_page_locks_target_and_forces_register_mode(): void
    {
        $owner = User::factory()->owner()->create();
        $employee = User::factory()->employee()->create([
            'pending_face_registration' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.attendance.face', [
                'user_id' => $employee->id,
                'mode' => 'register',
                'onboarding' => 1,
            ]))
            ->assertOk()
            ->assertSee('Pendaftaran Wajah Wajib')
            ->assertSee($employee->name)
            ->assertSee('Target terkunci selama proses pendaftaran wajib')
            ->assertSee('Daftarkan wajah ' . $employee->name)
            ->assertDontSee('Pilih Karyawan')
            ->assertDontSee('Presensi Hari Ini');
    }

    public function test_successful_registration_clears_pending_flag_and_returns_profile_redirect(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->employee()->create([
            'pending_face_registration' => true,
        ]);

        $this->actingAs($manager)
            ->postJson(route('admin.attendance.face.register'), [
                'user_id' => $employee->id,
                'embedding' => $this->embedding(),
                'liveness_score' => 0.95,
                'onboarding' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('user.has_face', true)
            ->assertJsonPath('user.pending_face_registration', false)
            ->assertJsonPath('redirect', route('admin.employees.show', $employee));

        $employee->refresh();

        $this->assertFalse($employee->pending_face_registration);
        $this->assertTrue($employee->hasFace());
        $this->assertNotNull($employee->face_registered_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'face.registered',
            'subject_id' => $employee->id,
            'user_id' => $manager->id,
        ]);
    }

    public function test_api_face_registration_also_clears_pending_flag(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->employee()->create([
            'pending_face_registration' => true,
        ]);

        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/face/register', [
            'user_id' => $employee->id,
            'embedding' => $this->embedding(),
            'liveness_score' => 0.95,
        ])
            ->assertOk()
            ->assertJsonPath('user.pending_face_registration', false);

        $this->assertFalse($employee->fresh()->pending_face_registration);
        $this->assertTrue($employee->fresh()->hasFace());
    }

    public function test_pending_account_cannot_login_through_web(): void
    {
        $employee = User::factory()->employee()->create([
            'pending_face_registration' => true,
        ]);

        $this->post(route('login'), [
            'email' => $employee->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($employee->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login_blocked',
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'auth.login',
            'user_id' => $employee->id,
        ]);
    }

    public function test_pending_account_cannot_login_through_api(): void
    {
        $employee = User::factory()->employee()->create([
            'pending_face_registration' => true,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $employee->email,
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame(0, $employee->tokens()->count());
    }

    public function test_account_can_login_after_face_registration_is_completed(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->employee()->create([
            'pending_face_registration' => true,
        ]);

        $this->actingAs($manager)
            ->postJson(route('admin.attendance.face.register'), [
                'user_id' => $employee->id,
                'embedding' => $this->embedding(),
                'liveness_score' => 0.95,
                'onboarding' => 1,
            ])
            ->assertOk();

        $this->assertFalse($employee->fresh()->pending_face_registration);

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();

        $this->post(route('login'), [
            'email' => $employee->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($employee->fresh());
    }

    public function test_creating_employee_without_code_auto_generates_employee_code(): void
    {
        $owner = User::factory()->owner()->create();
        $payload = $this->createEmployeePayload();
        unset($payload['employee_code']);
        $payload['email'] = 'nocode@stark-garage.local';

        $this->actingAs($owner)
            ->post(route('admin.employees.store'), $payload);

        $employee = User::where('email', 'nocode@stark-garage.local')->firstOrFail();
        $this->assertNotNull($employee->employee_code);
        $this->assertMatchesRegularExpression('/^STG-\d{3,}$/', $employee->employee_code);
    }

    public function test_all_team_roles_appear_in_employee_index(): void
    {
        $owner = User::factory()->owner()->create(['name' => 'Owner Person']);
        $manager = User::factory()->manager()->create(['name' => 'Manager Person']);
        $cashier = User::factory()->cashier()->create(['name' => 'Cashier Person']);
        $mechanic = User::factory()->employee()->create(['name' => 'Mechanic Person']);

        $response = $this->actingAs($owner)->get(route('admin.employees.index'));

        $response->assertOk();
        $response->assertSee('Owner Person');
        $response->assertSee('Manager Person');
        $response->assertSee('Cashier Person');
        $response->assertSee('Mechanic Person');
    }

    public function test_reset_face_route_resets_face_and_marks_pending(): void
    {
        $owner = User::factory()->owner()->create();
        $employee = User::factory()->employee()->create([
            'face_embedding' => $this->embedding(),
            'face_registered_at' => now(),
            'pending_face_registration' => false,
        ]);

        $this->assertTrue($employee->hasFace());

        $this->actingAs($owner)
            ->post(route('admin.employees.reset-face', $employee))
            ->assertRedirect();

        $fresh = $employee->fresh();
        $this->assertFalse($fresh->hasFace());
        $this->assertNull($fresh->face_registered_at);
        $this->assertTrue($fresh->pending_face_registration);
    }
}
