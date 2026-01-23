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
        // Schema::table('promotions', function(Blueprint $table){
        //     $table->dropForeign(['created_by']);
        //     $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        // Schema::table('promotions', function(Blueprint $table){
        //     $table->dropForeign(['created_by']);
        //     $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        // });
    }
};
