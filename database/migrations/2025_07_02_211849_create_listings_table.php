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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();

            // Core Info
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description');

            // Pricing
            $table->decimal('start_price', 10, 2);
            $table->decimal('reserve_price', 10, 2)->nullable();
            $table->decimal('buy_now_price', 10, 2)->nullable();
            $table->boolean('allow_offers')->default(false);
            $table->integer('quantity')->default(1);

            // Conditions
            $table->enum('condition', ['new', 'used']);
            $table->boolean('authenticated_bidders_only')->default(false);

            // Pickup/Shipping/Payment
            $table->enum('pickup_option', ['no_pickup', 'pickup_available', 'must_pickup'])->default('pickup_available');
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();

            // Product Attributes
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('brand')->nullable();
            $table->string('style')->nullable();

            // Category and Creator
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Meta
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // Flags
            $table->boolean('is_featured')->default(false);
            $table->tinyInteger('status')->default(1); // 1 = active, 0 = inactive, 2 = sold
            $table->timestamp('expire_at')->nullable();
            $table->timestamp('sold_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
