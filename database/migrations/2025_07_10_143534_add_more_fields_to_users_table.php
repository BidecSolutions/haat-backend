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
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->enum('account_type', ['business', 'personal'])->nullable()->after('last_name');
            $table->string('business_name')->nullable()->after('account_type');
            $table->string('country')->nullable()->after('business_name');
            $table->string('address_finder')->nullable()->after('country');
            $table->string('address_1')->nullable()->after('address_finder');
            $table->string('address_2')->nullable()->after('address_1');
            $table->string('suburb')->nullable()->after('address_2');
            $table->string('post_code')->nullable()->after('suburb');
            $table->string('closest_district')->nullable()->after('post_code');
            $table->string('landline')->nullable()->after('email');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
