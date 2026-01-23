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
        
        Schema::table('user_feedback', function (Blueprint $table) {
            // Step 1: Drop any foreign key that uses the index
            $table->dropForeign(['reviewer_id']);
            $table->dropForeign(['reviewed_user_id']);

            // Step 2: Drop the old unique index
            $table->dropUnique('user_feedback_reviewer_id_reviewed_user_id_unique');

            // Step 3: Add the new unique index with listing_id
            $table->unique(['reviewer_id', 'reviewed_user_id', 'listing_id']);

            // Step 4: Re-add foreign keys
            $table->foreign('reviewer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('user_feedback', function (Blueprint $table) {
            $table->dropForeign(['reviewer_id']);
            $table->dropForeign(['reviewed_user_id']);
            $table->dropUnique(['reviewer_id', 'reviewed_user_id', 'listing_id']);
            $table->unique(['reviewer_id', 'reviewed_user_id']);
            $table->foreign('reviewer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
