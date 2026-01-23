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
        Schema::table('listings', function (Blueprint $table) {
            $table->string('condition')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('listings', function (Blueprint $table) {
            $table->enum('condition', [
                'new',
                'used',
                'brand_new',
                'ready_to_move',
                'under_construction',
                'furnished',
                'semi_furnished',
                'unfurnished',
                'recently_renovated',
            ])->change();
        });
    }
};
