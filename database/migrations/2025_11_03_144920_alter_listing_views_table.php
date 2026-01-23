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
        Schema::table('listing_views', function (Blueprint $table) {
            // ✅ Check if foreign exists before dropping
            if (Schema::hasColumn('listing_views', 'listing_id')) {
                // Drop foreign key safely (use correct key name)
                $table->dropForeign(['listing_id']);
                $table->dropColumn('listing_id');
            }

            // Add polymorphic columns
            $table->unsignedBigInteger('viewable_id')->after('id');
            $table->string('viewable_type')->after('viewable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_views', function (Blueprint $table) {
            // Drop new polymorphic columns
            if (Schema::hasColumn('listing_views', 'viewable_id')) {
                $table->dropColumn(['viewable_id', 'viewable_type']);
            }

            // Restore old listing_id column + foreign
            $table->foreignId('listing_id')
                ->constrained('listings')
                ->cascadeOnDelete();
        });
    }
};
