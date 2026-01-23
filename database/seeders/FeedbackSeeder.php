<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeedbackSeeder extends Seeder
{
    public function run()
    {
        // Create Feedback Form
        $formId = DB::table('feedback_forms')->insertGetId([
            'title'         => 'boli bazar — Quick Feedback Form',
            'title_ar'      => 'معروض — نموذج ملاحظات سريع',
            'description'   => 'Collecting user feedback to improve boli bazar.',
            'description_ar'=> 'نجمع ملاحظات المستخدمين لتحسين منصة معروض.',
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // ------------------------------
        // QUESTION 1 — Emoji Experience
        // ------------------------------
        $q1 = DB::table('feedback_questions')->insertGetId([
            'form_id'            => $formId,
            'question_text'      => 'How was your experience on the boli website?',
            'question_text_ar'   => 'كيف كانت تجربتك على موقع معروض؟',
            'type'               => 'emoji',
            'is_required'        => true,
            'order'              => 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('feedback_question_options')->insert([
            [
                'question_id'     => $q1,
                'option_label'    => 'Good',
                'option_label_ar' => 'جيدة',
                'option_value'    => 'good',
                'emoji'           => '👍',
                'order'           => 1
            ],
            [
                'question_id'     => $q1,
                'option_label'    => 'Okay',
                'option_label_ar' => 'مقبولة',
                'option_value'    => 'okay',
                'emoji'           => '😐',
                'order'           => 2
            ],
            [
                'question_id'     => $q1,
                'option_label'    => 'Needs Improvement',
                'option_label_ar' => 'تحتاج لتحسين',
                'option_value'    => 'needs_improvement',
                'emoji'           => '👎',
                'order'           => 3
            ],
        ]);

        // ----------------------------------------------
        // QUESTION 2 — Easy to browse?
        // ----------------------------------------------
        $q2 = DB::table('feedback_questions')->insertGetId([
            'form_id'          => $formId,
            'question_text'    => 'Was it easy to browse and find items?',
            'question_text_ar' => 'هل كان من السهل التصفح والعثور على المنتجات؟',
            'type'             => 'radio',
            'is_required'      => true,
            'order'            => 2,
        ]);

        DB::table('feedback_question_options')->insert([
            [
                'question_id'     => $q2,
                'option_label'    => 'Yes',
                'option_label_ar' => 'نعم',
                'option_value'    => 'yes',
                'order'           => 1
            ],
            [
                'question_id'     => $q2,
                'option_label'    => 'Somewhat',
                'option_label_ar' => 'إلى حد ما',
                'option_value'    => 'somewhat',
                'order'           => 2
            ],
            [
                'question_id'     => $q2,
                'option_label'    => 'No',
                'option_label_ar' => 'لا',
                'option_value'    => 'no',
                'order'           => 3
            ],
        ]);

        // ----------------------------------------------
        // QUESTION 3 — Issues or bugs?
        // ----------------------------------------------
        $q3 = DB::table('feedback_questions')->insertGetId([
            'form_id'          => $formId,
            'question_text'    => 'Did you face any issues or bugs?',
            'question_text_ar' => 'هل واجهت أي مشاكل أو أخطاء؟',
            'type'             => 'radio',
            'is_required'      => true,
            'order'            => 3,
        ]);

        $q3_yes_option_id = DB::table('feedback_question_options')->insertGetId([
            'question_id'     => $q3,
            'option_label'    => 'Yes (please mention)',
            'option_label_ar' => 'نعم (يرجى التوضيح)',
            'option_value'    => 'yes',
            'order'           => 1,
        ]);

        DB::table('feedback_question_options')->insert([
            [
                'question_id'     => $q3,
                'option_label'    => 'No',
                'option_label_ar' => 'لا',
                'option_value'    => 'no',
                'order'           => 2
            ]
        ]);

        // ----------------------------------------------
        // CONDITIONAL SUB-QUESTION FOR Q3 YES
        // ----------------------------------------------
        DB::table('feedback_questions')->insert([
            'form_id'            => $formId,
            'question_text'      => 'Please describe the issue you faced.',
            'question_text_ar'   => 'يرجى وصف المشكلة التي واجهتها.',
            'type'               => 'text',
            'is_required'        => true,
            'order'              => 4,
            'is_conditional'     => true,
            'parent_question_id' => $q3,
            'parent_option_id'   => $q3_yes_option_id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // ----------------------------------------------
        // QUESTION 4 — Improve first?
        // ----------------------------------------------
        DB::table('feedback_questions')->insert([
            'form_id'          => $formId,
            'question_text'    => 'What is one thing we should improve first?',
            'question_text_ar' => 'ما هو الشيء الأول الذي يجب أن نحسّنه؟',
            'type'             => 'text',
            'is_required'      => false,
            'order'            => 5,
        ]);

        // ----------------------------------------------
        // QUESTION 5 — Feature request
        // ----------------------------------------------
        DB::table('feedback_questions')->insert([
            'form_id'          => $formId,
            'question_text'    => 'Which feature would you like us to add next?',
            'question_text_ar' => 'ما هي الميزة التي ترغب في إضافتها بعد ذلك؟',
            'type'             => 'text',
            'is_required'      => false,
            'order'            => 6,
        ]);

        // ----------------------------------------------
        // QUESTION 6 — Would you use boli?
        // ----------------------------------------------
        $q6 = DB::table('feedback_questions')->insertGetId([
            'form_id'          => $formId,
            'question_text'    => 'Would you use boli when we fully launch?',
            'question_text_ar' => 'هل ستستخدم معروض عند الإطلاق الكامل؟',
            'type'             => 'radio',
            'is_required'      => true,
            'order'            => 7,
        ]);

        DB::table('feedback_question_options')->insert([
            [
                'question_id'     => $q6,
                'option_label'    => 'Yes',
                'option_label_ar' => 'نعم',
                'option_value'    => 'yes',
                'order'           => 1
            ],
            [
                'question_id'     => $q6,
                'option_label'    => 'Maybe',
                'option_label_ar' => 'ربما',
                'option_value'    => 'maybe',
                'order'           => 2
            ],
            [
                'question_id'     => $q6,
                'option_label'    => 'No',
                'option_label_ar' => 'لا',
                'option_value'    => 'no',
                'order'           => 3
            ],
        ]);

        // ----------------------------------------------
        // QUESTION 7 — Other comments
        // ----------------------------------------------
        DB::table('feedback_questions')->insert([
            'form_id'          => $formId,
            'question_text'    => 'Any other comments?',
            'question_text_ar' => 'هل لديك أي ملاحظات أخرى؟',
            'type'             => 'text',
            'is_required'      => false,
            'order'            => 8,
        ]);
    }
}
