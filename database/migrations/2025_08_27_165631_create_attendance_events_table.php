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
        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches');
            $table->enum('event_type', ['check_in', 'check_out', 'manual_adjust', 'break_start', 'break_end']);
            $table->enum('source', ['mobile', 'kiosk', 'web', 'fp_device', 'manual']); // Source device/method
            $table->timestamp('event_time'); // When the event occurred
            $table->date('event_date'); // Date for easy querying
            
            // GPS and Location data
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('location_accuracy')->nullable(); // GPS accuracy in meters
            $table->boolean('is_within_geofence')->default(false);
            $table->string('location_address')->nullable();
            
            // Selfie and verification
            $table->string('selfie_path')->nullable();
            $table->boolean('selfie_verified')->default(false);
            $table->decimal('face_confidence', 5, 2)->nullable(); // Face recognition confidence
            
            // Device and session info
            $table->string('device_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            
            // Related schedule slot
            $table->foreignId('employee_shift_schedule_slot_id')
                  ->nullable()
                  ->constrained('employee_shift_schedule_slots')
                  ->onDelete('set null');
            
            // Additional data and metadata
            $table->json('metadata')->nullable(); // Additional event metadata
            $table->text('notes')->nullable();
            $table->string('event_hash')->unique(); // Hash to prevent duplicates
            
            // Correction/approval related
            $table->boolean('is_correction')->default(false);
            $table->foreignId('corrected_by')->nullable()->constrained('users');
            $table->text('correction_reason')->nullable();
            
            // Verification and processing
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['employee_id', 'event_date']);
            $table->index(['branch_id', 'event_date']);
            $table->index(['event_type', 'event_time']);
            $table->index(['is_processed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_events');
    }
};
