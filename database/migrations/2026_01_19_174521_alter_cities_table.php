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
        Schema::table('cities', function (Blueprint $table) {
            //
            $table->string('name_ar')->nullable()->change();
        });
        Schema::table('area', function (Blueprint $table) {
            //
            $table->string('name_ar')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            //
            $table->string('name_ar')->change();
        });
        Schema::table('area', function (Blueprint $table) {
            //
            $table->string('name_ar')->change();
        });
    }
};
