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
        Schema::table('employees', function (Blueprint $table) {
            // Add primary branch relationship
            $table->foreignId('primary_branch_id')->nullable()->constrained('branches')->after('position_id');
            
            // Enhanced employee information
            $table->string('face_encoding')->nullable()->after('avatar'); // For face recognition
            $table->json('allowed_branches')->nullable()->after('face_encoding'); // Which branches they can work at
            
            // Emergency contact
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            
            // Employment details
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time')->after('status');
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('employment_type');
            
            // System fields
            $table->timestamp('last_attendance_sync')->nullable()->after('employment_type');
            $table->json('settings')->nullable()->after('last_attendance_sync'); // Employee-specific settings
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['primary_branch_id']);
            $table->dropColumn([
                'primary_branch_id', 'face_encoding', 'allowed_branches',
                'emergency_contact_name', 'emergency_contact_phone',
                'employment_type', 'hourly_rate', 'last_attendance_sync', 'settings'
            ]);
        });
    }
};
