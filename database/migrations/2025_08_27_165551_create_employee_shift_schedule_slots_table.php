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
        Schema::create('employee_shift_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_shift_schedule_id')
                  ->constrained('employee_shift_schedules')
                  ->onDelete('cascade');
            $table->foreignId('shift_slot_id')->constrained('shift_slots');
            $table->time('actual_start_time')->nullable(); // Can be overridden from template
            $table->time('actual_end_time')->nullable(); // Can be overridden from template
            $table->integer('actual_duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('custom_breaks')->nullable(); // Override break times
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['employee_shift_schedule_id', 'shift_slot_id'], 'employee_schedule_slots_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_shift_schedule_slots');
    }
};
