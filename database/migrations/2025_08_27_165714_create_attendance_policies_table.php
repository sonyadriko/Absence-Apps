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
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Policy name (e.g., "Coffee Shop Default Policy")
            $table->text('description')->nullable();
            
            // Grace periods (in minutes)
            $table->integer('grace_late_min')->default(10); // Late tolerance
            $table->integer('grace_early_leave_min')->default(10); // Early leave tolerance
            $table->integer('flexible_start_window_min')->default(60); // Flexible start window
            
            // Geofence requirements
            $table->boolean('geofence_required')->default(true);
            $table->integer('geofence_radius_meters')->default(100);
            
            // Selfie requirements
            $table->boolean('selfie_required')->default(true);
            $table->decimal('min_face_confidence', 5, 2)->default(75.00); // Minimum face recognition confidence
            
            // Working time calculations
            $table->integer('min_work_minutes_for_present')->default(240); // 4 hours minimum to be considered present
            $table->time('night_shift_cutoff')->default('02:30'); // Cutoff time for night shift
            
            // Event priorities (JSON array)
            $table->json('event_priority')->default('["fp_device", "kiosk", "mobile", "web"]');
            
            // Overtime rules
            $table->boolean('auto_overtime_calculation')->default(true);
            $table->integer('overtime_threshold_minutes')->default(480); // 8 hours = standard day
            
            // Break rules
            $table->boolean('enforce_break_times')->default(false);
            $table->integer('max_break_duration_minutes')->default(60);
            
            // Notification settings
            $table->boolean('send_late_notifications')->default(true);
            $table->boolean('send_missing_checkout_notifications')->default(true);
            
            // System settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Is this the default policy?
            
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
        Schema::dropIfExists('attendance_policies');
    }
};
