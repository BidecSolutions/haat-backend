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
        Schema::table('listings', function (Blueprint $table) {
            $table->string('memory')->nullable()->after('style');
            $table->string('hard-drive-size')->nullable()->after('memory');
            $table->string('cores')->nullable()->after('hard-drive-size');
            $table->string('storage')->nullable()->after('cores');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            
        });
    }
};
