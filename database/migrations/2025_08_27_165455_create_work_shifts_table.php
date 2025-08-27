<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Pagi", "Siang", "Malam", "Split Pagi-Sore"
            $table->string('code')->unique(); // e.g., "PAGI", "SIANG", "MALAM", "SPLIT_PS"
            $table->text('description')->nullable();
            $table->enum('type', ['single', 'split'])->default('single'); // Single slot or split shift
            $table->integer('total_hours')->nullable(); // Expected total working hours
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Additional shift settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_shifts');
    }
};
