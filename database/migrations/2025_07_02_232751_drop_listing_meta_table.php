<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('listing_meta');
    }

    public function down(): void
    {
        Schema::create('listing_meta', function ($table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
            $table->timestamps();

            $table->unique(['listing_id', 'meta_key']);
        });
    }
};
