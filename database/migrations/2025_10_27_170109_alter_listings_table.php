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
        Schema::table('listings', function(Blueprint $table){
            $table->dropColumn('pickup_option');
            $table->integer('pickup_option')->default(1)->after('authenticated_bidders_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('listings', function(Blueprint $table){
            $table->dropColumn('pickup_option');
            $table->enum('pickup_option', ['no_pickup', 'pickup_available', 'must_pickup'])->default('pickup_available')->after('authenticated_bidders_only');
        });
    }
};
