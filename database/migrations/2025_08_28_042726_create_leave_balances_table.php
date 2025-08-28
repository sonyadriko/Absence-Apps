<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->year('year'); // Leave balance year
            $table->decimal('allocated_days', 5, 2)->default(0);
            $table->decimal('used_days', 5, 2)->default(0);
            $table->decimal('carry_forward_days', 5, 2)->default(0);
            $table->decimal('remaining_days', 5, 2)->default(0);
            $table->date('expires_at')->nullable(); // When carry forward expires
            $table->timestamps();
            
            // Unique constraint to prevent duplicate balances
            $table->unique(['employee_id', 'leave_type_id', 'year']);
            
            // Indexes for better performance
            $table->index(['employee_id', 'year']);
            $table->index(['leave_type_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
