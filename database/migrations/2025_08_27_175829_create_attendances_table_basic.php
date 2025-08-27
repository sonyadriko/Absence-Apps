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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('employee_shift_schedule_id')->nullable()->constrained('employee_shift_schedules');
            $table->date('date');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->time('scheduled_start')->nullable();
            $table->time('scheduled_end')->nullable();
            $table->timestamp('actual_check_in')->nullable();
            $table->timestamp('actual_check_out')->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'half_day'])->default('present');
            $table->integer('late_minutes')->default(0);
            $table->integer('early_minutes')->default(0);
            $table->integer('total_work_minutes')->default(0);
            $table->integer('break_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->json('events_summary')->nullable();
            $table->boolean('has_corrections')->default(false);
            $table->json('correction_history')->nullable();
            $table->timestamp('last_computed_at')->nullable();
            $table->string('computation_version')->nullable();
            $table->json('location_data')->nullable();
            $table->string('location_check_in')->nullable(); // Keep for backward compatibility
            $table->string('location_check_out')->nullable(); // Keep for backward compatibility
            $table->timestamps();
            
            $table->unique(['employee_id', 'date']); // One attendance record per employee per day
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
