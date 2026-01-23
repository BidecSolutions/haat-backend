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
        Schema::create('vehicle_data', function (Blueprint $table) {
            $table->id();

            // Make (brand)
            $table->string('make')->index(); // e.g., Honda
            $table->string('make_slug')->nullable(); // for normalized searching

            // Model
            $table->string('model')->nullable()->index(); // e.g., Civic
            $table->string('model_slug')->nullable();

            // Year
            $table->integer('year')->nullable()->index();

            // Optional fields from APIs
            $table->string('body_style')->nullable();
            $table->string('vehicle_type')->nullable(); // sedan, SUV, etc.

            $table->timestamps();

            // Composite index for faster lookups
            $table->unique(['make', 'model', 'year'], 'make_model_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_data');
    }
};
