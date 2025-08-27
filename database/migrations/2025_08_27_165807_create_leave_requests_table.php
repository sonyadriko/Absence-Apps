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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days'); // Working days requested
            $table->text('reason');
            $table->string('document_path')->nullable(); // Supporting document
            
            // Approval workflow
            $table->enum('status', ['pending', 'approved_by_pengelola', 'approved_by_manager', 'approved_by_hr', 'approved', 'rejected'])
                  ->default('pending');
            
            // Approval chain tracking
            $table->foreignId('pengelola_approved_by')->nullable()->constrained('users');
            $table->timestamp('pengelola_approved_at')->nullable();
            $table->text('pengelola_notes')->nullable();
            
            $table->foreignId('manager_approved_by')->nullable()->constrained('users');
            $table->timestamp('manager_approved_at')->nullable();
            $table->text('manager_notes')->nullable();
            
            $table->foreignId('hr_approved_by')->nullable()->constrained('users');
            $table->timestamp('hr_approved_at')->nullable();
            $table->text('hr_notes')->nullable();
            
            $table->foreignId('final_approved_by')->nullable()->constrained('users');
            $table->timestamp('final_approved_at')->nullable();
            
            // Rejection tracking
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            
            $table->index(['employee_id', 'start_date', 'end_date']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_requests');
    }
};
