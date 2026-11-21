<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 30)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar_path')->nullable();
            $table->string('role', 20)->default('employee')->index();
            $table->foreignId('shift_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->json('face_embedding')->nullable();
            $table->timestamp('face_registered_at')->nullable();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->string('salary_type', 20)->default('monthly');
            $table->decimal('hourly_overtime_rate', 12, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn([
                'employee_code', 'phone', 'avatar_path', 'role', 'shift_id',
                'face_embedding', 'face_registered_at', 'base_salary', 'salary_type',
                'hourly_overtime_rate', 'is_active', 'last_login_at',
            ]);
        });
    }
};
