<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('student_class');
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->string('device_id');
            $table->enum('status', ['online', 'offline', 'violation'])->default('online');
            $table->timestamp('last_ping_at')->nullable();
            $table->unsignedInteger('violation_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
