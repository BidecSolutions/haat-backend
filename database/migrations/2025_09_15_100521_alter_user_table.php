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
        Schema::table('users', function(Blueprint $table){
             if(!Schema::hasColumn('users', 'tax_id')){
                $table->string('tax_id')->nullable()->after('business_name');
            }
            if(!Schema::hasColumn('users', 'business_license')){
                $table->string('business_license')->nullable()->after('tax_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('users', function(Blueprint $table){
            if(Schema::hasColumn('users', 'tax_id')){
                $table->dropColumn('tax_id');
            }
            if(Schema::hasColumn('users', 'business_license')){
                $table->dropColumn('business_license');
            }
        });
    }
};
