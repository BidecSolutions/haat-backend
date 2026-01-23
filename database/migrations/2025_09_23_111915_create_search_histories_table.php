<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('keyword');
            $table->unsignedInteger('count')->default(1); // how many times searched
            $table->timestamps();

            $table->unique(['user_id', 'keyword']); // avoid duplicate entries for same user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};
