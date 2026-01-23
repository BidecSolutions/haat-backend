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
        Schema::table('feedback_forms', function (Blueprint $table) {
            $table->string('title_ar')->nullable();
            $table->text('description_ar')->nullable();
        });

        Schema::table('feedback_questions', function (Blueprint $table) {
            $table->text('question_text_ar')->nullable();
        });

        Schema::table('feedback_question_options', function (Blueprint $table) {
            $table->string('option_label_ar')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('feedback_forms', function (Blueprint $table) {
            $table->dropColumn('title_ar');
            $table->dropColumn('description_ar');
        });

        Schema::table('feedback_questions', function (Blueprint $table) {
            $table->dropColumn('question_text_ar');
        });

        Schema::table('feedback_question_options', function (Blueprint $table) {
            $table->dropColumn('option_label_ar');
        });

    }
};
