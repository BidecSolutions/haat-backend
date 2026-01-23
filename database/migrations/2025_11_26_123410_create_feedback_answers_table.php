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
        Schema::create('feedback_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('response_id')->constrained('feedback_responses')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('feedback_questions')->onDelete('cascade');
            // If multiple choice
            $table->foreignId('option_id')->nullable()->constrained('feedback_question_options')->onDelete('set null');
            // If text-based
            $table->text('answer_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_answers');
    }
};
