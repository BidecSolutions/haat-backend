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
            $table->string('feedback_type')->after('feedback_text');
            $table->foreignId('listing_id')->nullable()->after('feedback_type')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_feedback', function (Blueprint $table) {
            $table->dropColumn('feedback_type');
            $table->dropForeign(['listing_id']);
            $table->dropColumn('listing_id');
        });
    }
};
