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
        Schema::create('shift_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_shift_id')->constrained('work_shifts')->onDelete('cascade');
            $table->string('name'); // e.g., "Morning Slot", "Evening Slot"
            $table->time('start_time'); // Start time of this slot
            $table->time('end_time'); // End time of this slot
            $table->integer('order')->default(1); // Order for multiple slots
            $table->integer('duration_minutes'); // Duration in minutes
            $table->boolean('is_overnight')->default(false); // Does this slot span midnight?
            $table->json('break_times')->nullable(); // Break periods within this slot
            $table->timestamps();
            
            $table->index(['work_shift_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_slots');
    }
};
