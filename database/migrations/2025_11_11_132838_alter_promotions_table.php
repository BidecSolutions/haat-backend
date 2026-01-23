<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('redirect_type')->default('url');
            $table->string('internal_route')->nullable();
            $table->enum('target', ['_self', '_blank'])->default('_self');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('redirect_type');
            $table->dropColumn('internal_route');
            $table->dropColumn('target');
        });
    }
};
