<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('feedback_forms', 'title_bn')) {
                $table->string('title_bn')->nullable()->after('title_ar');
            }
            if (!Schema::hasColumn('feedback_forms', 'description_bn')) {
                $table->text('description_bn')->nullable()->after('description_ar');
            }
        });

        Schema::table('feedback_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('feedback_questions', 'question_text_bn')) {
                $table->text('question_text_bn')->nullable()->after('question_text_ar');
            }
        });

        Schema::table('feedback_question_options', function (Blueprint $table) {
            if (!Schema::hasColumn('feedback_question_options', 'option_label_bn')) {
                $table->string('option_label_bn')->nullable()->after('option_label_ar');
            }
        });

        // Populate Bangla for existing feedback form (matches boli/Haat seeder)
        $form = DB::table('feedback_forms')->where('is_active', true)->first();
        if ($form) {
            DB::table('feedback_forms')->where('id', $form->id)->update([
                'title_bn' => 'হাত — দ্রুত মতামত ফর্ম',
                'description_bn' => 'হাত প্ল্যাটফর্ম উন্নত করতে ব্যবহারকারীর মতামত সংগ্রহ করা হচ্ছে।',
            ]);

            $questions = DB::table('feedback_questions')->where('form_id', $form->id)->get();
            $bnMap = [
                'How was your experience on the boli website?' => 'হাত ওয়েবসাইটে আপনার অভিজ্ঞতা কেমন ছিল?',
                'Was it easy to browse and find items?' => 'আইটেম ব্রাউজ এবং খুঁজে পেতে কি সহজ ছিল?',
                'Did you face any issues or bugs?' => 'আপনি কি কোনো সমস্যা বা বাগের সম্মুখীন হয়েছেন?',
                'Please describe the issue you faced.' => 'আপনার সম্মুখীন সমস্যার বর্ণনা দিন।',
                'What is one thing we should improve first?' => 'আমাদের প্রথমে কী একটি জিনিস উন্নতি করা উচিত?',
                'Which feature would you like us to add next?' => 'পরবর্তীতে কোন বৈশিষ্ট্য যোগ করতে চান?',
                'Would you use boli when we fully launch?' => 'পুরোপুরি চালু হলে আপনি কি হাত ব্যবহার করবেন?',
                'Any other comments?' => 'অন্যান্য মন্তব্য?',
            ];
            foreach ($questions as $q) {
                if (isset($bnMap[$q->question_text])) {
                    DB::table('feedback_questions')->where('id', $q->id)->update(['question_text_bn' => $bnMap[$q->question_text]]);
                }
            }

            $optMap = ['Good' => 'ভালো', 'Okay' => 'ঠিক আছে', 'Needs Improvement' => 'উন্নতির প্রয়োজন',
                'Yes' => 'হ্যাঁ', 'Somewhat' => 'কিছুটা', 'No' => 'না',
                'Yes (please mention)' => 'হ্যাঁ (দয়া করে উল্লেখ করুন)', 'Maybe' => 'হয়তো'];
            foreach ($optMap as $en => $bn) {
                DB::table('feedback_question_options')->where('option_label', $en)->update(['option_label_bn' => $bn]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('feedback_forms', function (Blueprint $table) {
            $table->dropColumn(['title_bn', 'description_bn']);
        });
        Schema::table('feedback_questions', function (Blueprint $table) {
            $table->dropColumn('question_text_bn');
        });
        Schema::table('feedback_question_options', function (Blueprint $table) {
            $table->dropColumn('option_label_bn');
        });
    }
};
