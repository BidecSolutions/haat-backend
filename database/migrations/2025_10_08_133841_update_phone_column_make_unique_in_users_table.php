<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE users SET phone = NULL");

        // Try to drop the index manually with SQL, ignore if not found
        try {
            DB::statement("ALTER TABLE users DROP INDEX users_phone_unique");
        } catch (\Throwable $e) {
            // ignore if index doesn't exist
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->change();
        });
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE users DROP INDEX users_phone_unique");
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });
    }
};
