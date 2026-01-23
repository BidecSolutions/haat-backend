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
        Schema::table('users', function (Blueprint $table) {
        $table->string('occupation')->nullable()->after('account_type'); 
        $table->string('about_me')->nullable()->after('occupation'); // Adding phone field
        $table->string('favourite_quote')->nullable()->after('about_me'); // Adding phone field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn(['occupation', 'about_me', 'favourite_quote']);
        });
    }
};
