<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'teacher', 'admin', 'guru', 'pengawas') NOT NULL DEFAULT 'teacher'");
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'teacher')->update(['role' => 'guru']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'guru', 'pengawas') NOT NULL DEFAULT 'guru'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'teacher', 'admin', 'guru', 'pengawas') NOT NULL DEFAULT 'teacher'");
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'guru')->update(['role' => 'teacher']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'teacher') NOT NULL DEFAULT 'teacher'");
    }
};
