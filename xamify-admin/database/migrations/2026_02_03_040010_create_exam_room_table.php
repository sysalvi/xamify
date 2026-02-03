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
        if (Schema::hasTable('exam_room')) {
            Schema::table('exam_room', function (Blueprint $table) {
                if (! Schema::hasColumn('exam_room', 'exam_id')) {
                    $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
                }

                if (! Schema::hasColumn('exam_room', 'room_id')) {
                    $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                }

                if (! Schema::hasColumn('exam_room', 'created_at')) {
                    $table->timestamps();
                }
            });

            return;
        }

        Schema::create('exam_room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_id', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_room');
    }
};
