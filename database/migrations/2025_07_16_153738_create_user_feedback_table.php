<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_user_id')->constrained('users')->onDelete('cascade');

            $table->tinyInteger('rating')->unsigned()->comment('1 to 5 stars');
            $table->text('feedback_text')->nullable();

            $table->timestamps();

            $table->unique(['reviewer_id', 'reviewed_user_id']); // only one feedback per user → user
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedback');
    }
};
