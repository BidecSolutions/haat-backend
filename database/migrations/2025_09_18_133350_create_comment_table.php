<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**database/migrations/2025_09_18_133350_create_comment_table.php
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment_text');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->timestamps();

            $table->index(['listing_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
