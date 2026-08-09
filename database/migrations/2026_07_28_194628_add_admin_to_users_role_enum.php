<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel compiles enum() as a varchar + CHECK constraint. On Postgres,
     * ->change() tries to fold that CHECK into an "alter column ... type"
     * clause, which is invalid SQL there (works fine on MySQL/SQLite), so
     * Postgres needs the constraint dropped and re-added explicitly.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table users drop constraint users_role_check');
            DB::statement("alter table users add constraint users_role_check check (role in ('student', 'staff', 'doctor', 'admin'))");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['student', 'staff', 'doctor', 'admin'])->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table users drop constraint users_role_check');
            DB::statement("alter table users add constraint users_role_check check (role in ('student', 'staff', 'doctor'))");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['student', 'staff', 'doctor'])->change();
            });
        }
    }
};
