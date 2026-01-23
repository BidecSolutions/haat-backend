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
      Schema::create('listing_reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('listing_id')->constrained('listings')->onDelete('cascade');
    $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('reviewed_user_id')->constrained('users')->onDelete('cascade');
    $table->tinyInteger('rating')->unsigned()->comment('1 to 5 stars');
    $table->text('review_text')->nullable();
    $table->timestamps();

    // ✅ Only one review per listing from a user to another user
    $table->unique(['listing_id', 'reviewer_id', 'reviewed_user_id'], 'unique_listing_review');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_reviews');
    }
};
