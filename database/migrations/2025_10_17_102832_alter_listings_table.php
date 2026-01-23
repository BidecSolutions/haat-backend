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
        Schema::table('listings', function(Blueprint $table){
            $table->decimal('longitude', 10, 7)->nullable()->after('payment_method_id');
            $table->decimal('latitude', 10, 7)->nullable()->after('longitude');
            $table->string('address')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('listings', function(Blueprint $table){
            $table->dropColumn('longitude');
            $table->dropColumn('latitude');
            $table->dropColumn('address');
        });
    }
};
