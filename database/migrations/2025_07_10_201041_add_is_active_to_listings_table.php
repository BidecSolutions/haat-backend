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
            
        Schema::table('listings', function (Blueprint $table) {
            $table->tinyInteger('status')
                ->default(0)
                ->comment('0=pending, 1=approved, 2=rejected, 3=sold, 4=expired')
                ->change();
            $table->boolean('is_active')->default(true)->after('status'); // true = active
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->change(); // remove comment
            $table->dropColumn('is_active'); // remove is_active column
        });
    }
};
