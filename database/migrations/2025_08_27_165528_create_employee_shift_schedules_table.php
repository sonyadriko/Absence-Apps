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
        Schema::create('employee_shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('work_shift_id')->constrained('work_shifts');
            $table->date('schedule_date'); // The specific date for this schedule
            $table->enum('status', ['scheduled', 'cancelled', 'completed'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->json('custom_settings')->nullable(); // Override shift settings per employee/date
            $table->foreignId('created_by')->constrained('users'); // Who created this schedule
            $table->timestamps();
            
            $table->unique(['employee_id', 'schedule_date', 'work_shift_id'], 'emp_schedule_unique'); // Prevent duplicate schedules
            $table->index(['branch_id', 'schedule_date']);
            $table->index(['schedule_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_shift_schedules');
    }
};
