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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2); // current bid amount
            $table->decimal('max_auto_bid_amount', 10, 2)->nullable(); // only for auto-bids
            $table->enum('type', ['manual', 'auto'])->default('manual');
            $table->tinyInteger('status')->default(1); // 1: active, 0: inactive
            $table->timestamp('bid_time')->useCurrent(); // time of the bid
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
