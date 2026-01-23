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
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('hard-drive-size', 'hard_drive_size');
        });
    }

    public function down()
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('hard_drive_size', 'hard-drive-size');
        });
    }
};
