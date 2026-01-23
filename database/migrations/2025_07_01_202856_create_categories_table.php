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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();

            // Core
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->tinyInteger('status')->default(1); // 1 = active, 0 = inactive

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('schema')->nullable(); // Store as JSON-LD string
            $table->string('canonical_url')->nullable();
            $table->string('focus_keywords')->nullable();

            // Redirects
            $table->string('redirect_301')->nullable();
            $table->string('redirect_302')->nullable();

            // Media
            $table->string('icon')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_path_name')->nullable();
            $table->string('image_path_alt_name')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
