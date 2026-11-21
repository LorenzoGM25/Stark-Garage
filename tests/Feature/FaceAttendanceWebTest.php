<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceAttendanceWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function embedding(float $value = 0.1): array
    {
        return array_fill(0, 128, $value);
    }

    /** @param array<int, float> $head */
    private function vector(array $head): array
    {
        $values = array_fill(0, 128, 0.0);

        foreach ($head as $index => $value) {
            $values[$index] = $value;
        }

        return $values;
    }

    public function test_attendance_face_page_renders_for_employee(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->get(route('admin.attendance.face'))
            ->assertOk()
            ->assertSee('Pemindai Wajah')
            ->assertSee('Presensi Hari Ini')
            ->assertSee('face-api.js', false);
    }

    public function test_employee_can_register_own_face(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $this->embedding(),
                'liveness_score' => 0.95,
            ])
            ->assertOk()
            ->assertJsonPath('user.has_face', true);

        $this->assertTrue($employee->fresh()->hasFace());
        $this->assertNotNull($employee->fresh()->face_registered_at);
    }

    public function test_employee_cannot_register_another_users_face(): void
    {
        $employee = User::factory()->employee()->create();
        $other = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.register'), [
                'user_id' => $other->id,
                'embedding' => $this->embedding(),
                'liveness_score' => 0.95,
            ])
            ->assertForbidden();
    }

    public function test_manager_can_register_another_employees_face(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->employee()->create();

        $this->actingAs($manager)
            ->postJson(route('admin.attendance.face.register'), [
                'user_id' => $employee->id,
                'embedding' => $this->embedding(),
                'liveness_score' => 0.95,
            ])
            ->assertOk();

        $this->assertTrue($employee->fresh()->hasFace());
    }

    public function test_face_recognition_clocks_in_then_clocks_out(): void
    {
        $employee = User::factory()->employee()->create();

        $embedding = $this->embedding();

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $embedding,
                'liveness_score' => 0.95,
            ])
            ->assertOk();

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.recognize'), [
                'embedding' => $embedding,
                'liveness_score' => 0.95,
            ])
            ->assertOk()
            ->assertJsonPath('action', 'clock_in');

        $attendance = Attendance::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertNotNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.recognize'), [
                'embedding' => $embedding,
                'liveness_score' => 0.95,
            ])
            ->assertOk()
            ->assertJsonPath('action', 'clock_out');

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertSame(1, Attendance::query()->where('user_id', $employee->id)->count());
    }

    public function test_face_recognition_rejects_unregistered_face(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.recognize'), [
                'embedding' => $this->embedding(),
                'liveness_score' => 0.95,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('embedding');
    }

    public function test_low_liveness_score_is_rejected(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $this->embedding(),
                'liveness_score' => 0.2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('liveness_score');

        $this->assertFalse($employee->fresh()->hasFace());
    }

    public function test_employee_cannot_clock_in_with_another_employees_face(): void
    {
        $first = User::factory()->employee()->create();
        $second = User::factory()->employee()->create();

        $firstFace = $this->vector([1.0]);
        $secondFace = $this->vector([0.9, 0.43589]);

        $this->actingAs($first)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $firstFace,
                'liveness_score' => 0.95,
            ])
            ->assertOk();

        $this->actingAs($second)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $secondFace,
                'liveness_score' => 0.95,
            ])
            ->assertOk();

        $this->actingAs($first)
            ->postJson(route('admin.attendance.face.recognize'), [
                'embedding' => $secondFace,
                'liveness_score' => 0.95,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('embedding');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_face_recognition_rejects_ambiguous_match(): void
    {
        $first = User::factory()->employee()->create();
        $second = User::factory()->employee()->create();

        $firstFace = $this->vector([1.0]);
        $secondFace = $this->vector([0.9, 0.43589]);

        // Normalized combination of both faces, slightly closer to the first one.
        $ambiguousFace = $this->vector([0.97494, 0.22248]);

        $this->actingAs($first)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $firstFace,
                'liveness_score' => 0.95,
            ])
            ->assertOk();

        $this->actingAs($second)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $secondFace,
                'liveness_score' => 0.95,
            ])
            ->assertOk();

        $response = $this->actingAs($first)
            ->postJson(route('admin.attendance.face.recognize'), [
                'embedding' => $ambiguousFace,
                'liveness_score' => 0.95,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('embedding');

        $this->assertStringContainsString('terlalu mirip', $response->json('errors.embedding.0'));
        $this->assertDatabaseCount('attendances', 0);
    }
}
