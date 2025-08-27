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
        // Skip this migration - attendances table already created with all fields
        return;
        
        Schema::table('attendances', function (Blueprint $table) {
            // Add relationships to new system
            $table->foreignId('branch_id')->nullable()->constrained('branches')->after('employee_id');
            $table->foreignId('employee_shift_schedule_id')->nullable()
                  ->constrained('employee_shift_schedules')->after('branch_id');
            
            // Working time calculations  
            $table->integer('total_work_minutes')->default(0)->after('early_minutes');
            $table->integer('break_minutes')->default(0)->after('total_work_minutes');
            $table->integer('overtime_minutes')->default(0)->after('break_minutes');
            
            // Detailed time tracking
            $table->time('scheduled_start')->nullable()->after('check_in');
            $table->time('scheduled_end')->nullable()->after('scheduled_start');
            $table->timestamp('actual_check_in')->nullable()->after('check_out');
            $table->timestamp('actual_check_out')->nullable()->after('actual_check_in');
            
            // Event tracking
            $table->json('events_summary')->nullable()->after('notes'); // Summary of all events for this day
            $table->boolean('has_corrections')->default(false)->after('events_summary');
            $table->json('correction_history')->nullable()->after('has_corrections');
            
            // Processing metadata
            $table->timestamp('last_computed_at')->nullable()->after('correction_history');
            $table->string('computation_version')->nullable()->after('last_computed_at'); // Track computation logic version
            
            // Store all location-related data as JSON (avoid dropping columns for now)
            $table->json('location_data')->nullable()->after('computation_version');
        });
        
        // Note: Enhanced status enum will be handled at application level for now
        // Column changes require doctrine/dbal which has compatibility issues with Laravel 9
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['employee_shift_schedule_id']);
            $table->dropColumn([
                'branch_id', 'employee_shift_schedule_id', 'total_work_minutes',
                'break_minutes', 'overtime_minutes', 'scheduled_start', 'scheduled_end',
                'actual_check_in', 'actual_check_out', 'events_summary', 'has_corrections',
                'correction_history', 'last_computed_at', 'computation_version', 'location_data'
            ]);
            
            $table->string('location_check_in')->nullable();
            $table->string('location_check_out')->nullable();
            
            $table->enum('status', ['present', 'late', 'absent', 'half_day'])->change();
        });
    }
};
