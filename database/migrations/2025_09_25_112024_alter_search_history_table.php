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
        Schema::table('search_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('keyword');
            $table->string('guest_id')->nullable()->after('user_id');
            $table->string('category_path')->nullable()->after('category_id');
            $table->json('filters')->nullable()->after('category_id');

            // $table->check('(user_id IS NOT NULL AND guest_id IS NULL) OR (user_id IS NULL AND guest_id IS NOT NULL)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('search_histories', function (Blueprint $table) {
            $table->dropColumn('category_id');
            $table->dropColumn('category_path');
            $table->dropColumn('guest_id');
            $table->dropColumn('filters');
        });
    }
};
