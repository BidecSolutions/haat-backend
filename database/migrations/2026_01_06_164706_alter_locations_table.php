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
        //
        Schema::table('regions', function (Blueprint $table) {
            $table->string('name_ar')->after('name');
        });
        Schema::table('governorates', function (Blueprint $table) {
            $table->string('name_ar')->after('name');
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->string('name_ar')->after('name');
        });
        Schema::create('area', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->string('name');
            $table->string('name_ar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });
        Schema::table('governorates', function (Blueprint $table) {
            $table->dropColumn('name_ar')->after('name');
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('name_ar')->after('name');
        });
        Schema::dropIfExists('area');
    }
};
