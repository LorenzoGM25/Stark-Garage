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
        \Illuminate\Support\Facades\Storage::fake('public');

        $employee = User::factory()->employee()->create();
        $sampleBase64 = 'data:image/jpeg;base64,' . base64_encode('fake-jpg-binary-content');

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.register'), [
                'embedding' => $this->embedding(),
                'liveness_score' => 0.95,
                'photo_base64' => $sampleBase64,
            ])
            ->assertOk()
            ->assertJsonPath('user.has_face', true);

        $fresh = $employee->fresh();
        $this->assertTrue($fresh->hasFace());
        $this->assertNotNull($fresh->face_registered_at);
        $this->assertNotNull($fresh->avatar_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($fresh->avatar_path);
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
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ])
            ->assertOk()
            ->assertJsonPath('action', 'clock_in');

        $attendance = Attendance::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertNotNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);
        $this->assertEquals(-6.2088, (float) $attendance->clock_in_latitude);
        $this->assertEquals(106.8456, (float) $attendance->clock_in_longitude);

        $this->actingAs($employee)
            ->postJson(route('admin.attendance.face.recognize'), [
                'embedding' => $embedding,
                'liveness_score' => 0.95,
                'latitude' => -6.2090,
                'longitude' => 106.8460,
            ])
            ->assertOk()
            ->assertJsonPath('action', 'clock_out');

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertEquals(-6.2090, (float) $attendance->clock_out_latitude);
        $this->assertEquals(106.8460, (float) $attendance->clock_out_longitude);
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

        $firstFace = $this->vector([1.0, 0.0]);
        $secondFace = $this->vector([0.0, 1.0]);

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

        // Two distinct employees with distinct embeddings (similarity between them = 0.445, well below threshold 0.82)
        $firstFace = $this->vector([0.85, 0.52678]);
        $secondFace = $this->vector([0.85, -0.52678]);

        // Ambiguous face that matches both equally (score = 0.85 for both, margin = 0.0)
        $ambiguousFace = $this->vector([1.0, 0.0]);

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

    public function test_manager_or_owner_can_recognize_and_clock_in_any_employee(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->employee()->create();

        $employeeFace = $this->embedding();

        $this->actingAs($manager)
            ->postJson(route('admin.attendance.face.register'), [
                'user_id' => $employee->id,
                'embedding' => $employeeFace,
                'liveness_score' => 0.95,
            ])
            ->assertOk();

        // When Manager opens the garage attendance kiosk, employee can scan and clock in
        $response = $this->actingAs($manager)
            ->postJson(route('admin.attendance.face.recognize'), [
                'embedding' => $employeeFace,
                'liveness_score' => 0.95,
            ])
            ->assertOk()
            ->assertJsonPath('action', 'clock_in')
            ->assertJsonPath('user.id', $employee->id);

        $attendance = Attendance::query()->where('user_id', $employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_in);
    }

    public function test_manager_can_reset_employee_face(): void
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

        $this->actingAs($manager)
            ->post(route('admin.employees.reset-face', $employee))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($employee->fresh()->hasFace());
        $this->assertTrue($employee->fresh()->needsFaceRegistration());
    }
}
