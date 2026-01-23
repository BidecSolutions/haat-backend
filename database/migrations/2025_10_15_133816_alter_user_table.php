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
            if(!Schema::hasColumn('users', 'reset_p_code')){
                $table->string('reset_p_code')->nullable()->after('verification_expires_at');
            }
            if(!Schema::hasColumn('users', 'reset_p_code_expire_at')){
                $table->timestamp('reset_p_code_expire_at')->nullable()->after('reset_p_code');
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
            if(Schema::hasColumn('users', 'reset_p_code')){
                $table->dropColumn('reset_p_code');
            }
            if(Schema::hasColumn('users', 'reset_p_code_expire_at')){
                $table->dropColumn('reset_p_code_expire_at');
            }
        });
    }
};
