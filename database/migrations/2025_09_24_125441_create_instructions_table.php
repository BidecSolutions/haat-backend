<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instructions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();          // required in controller
            $table->text('description')->nullable();      // required in controller
            $table->string('image')->nullable();          // required in controller
            $table->string('module')->nullable();         // handled via Enum
            $table->integer('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructions');
    }
};
