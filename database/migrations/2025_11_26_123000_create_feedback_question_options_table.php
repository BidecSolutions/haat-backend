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
        Schema::create('feedback_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('feedback_questions')->onDelete('cascade');
            
            $table->string('option_label');    // e.g. Good / Okay / Bad
            $table->string('option_value');    // good / okay / bad
            $table->string('emoji')->nullable(); // 🟢 🟡 🔴 or 👍 😐 👎

            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_question_options');
    }
};
