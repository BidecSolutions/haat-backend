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
        Schema::table('listing_offers', function (Blueprint $table) {
            //adding new fields to the listing_offers table
            // $table->timestamp('expires_at')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_offers', function (Blueprint $table) {
            //dropping the newly added fields
            $table->dropColumn(['expires_at', 'responded_at']);
            
        });
    }
};
