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
        //
        Schema::dropIfExists('codes');
        Schema::create('codes', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('value', 255)->nullable(); 
            $table->string('sort_order')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['key', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('codes');
        Schema::create('codes', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('sort_order')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }
};
