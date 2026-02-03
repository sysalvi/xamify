<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('access_code')->nullable()->after('device_id');
        });

        DB::statement("ALTER TABLE exam_sessions MODIFY status ENUM('online', 'offline', 'violation', 'locked') NOT NULL DEFAULT 'online'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE exam_sessions MODIFY status ENUM('online', 'offline', 'violation') NOT NULL DEFAULT 'online'");

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }
};
