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
        Schema::table('feedback_questions', function (Blueprint $table) {
            $table->boolean('is_conditional')->default(false)->after('is_required');
            $table->unsignedBigInteger('parent_question_id')->nullable()->after('form_id');
            $table->unsignedBigInteger('parent_option_id')->nullable()->after('parent_question_id');
            $table->foreign('parent_question_id')->references('id')->on('feedback_questions')->onDelete('cascade');
            $table->foreign('parent_option_id')->references('id')->on('feedback_question_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback_questions', function (Blueprint $table) {
            //
            $table->dropForeign('parent_question_id');
            $table->dropForeign('parent_option_id');
            $table->dropColumn(['parent_question_id', 'parent_option_id', 'is_conditional', 'has_sub_questions']);
        });
    }
};
