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
        Schema::table('listings', function(Blueprint $table){
            $table->integer('country_id')->nullable()->after('listing_type');
            $table->integer('regions_id')->nullable()->after('country_id');
            $table->integer('governorates_id')->nullable()->after('regions_id');
            $table->integer('city_id')->nullable()->after('governorates_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function(Blueprint $table){
            $table->dropColumn('country_id');
            $table->dropColumn('regions_id');
            $table->dropColumn('governorates_id');
            $table->dropColumn('city_id');
        });
    }
};
