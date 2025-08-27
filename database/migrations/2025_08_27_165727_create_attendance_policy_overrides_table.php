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
        Schema::create('attendance_policy_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_policy_id')->constrained('attendance_policies')->onDelete('cascade');
            
            // What this override applies to (hierarchy: specific employee > position > branch)
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            
            // Override fields (only non-null fields will override the policy)
            $table->string('override_field'); // Which policy field to override
            $table->text('override_value'); // The new value (stored as text, cast appropriately)
            $table->text('reason')->nullable(); // Why this override exists
            
            // Validity period
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes and constraints
            $table->index(['branch_id', 'position_id', 'employee_id'], 'policy_overrides_scope_idx');
            $table->index(['override_field', 'is_active'], 'policy_overrides_field_idx');
            
            // Note: Check constraint for ensuring only one of branch/position/employee is set
            // should be enforced at application level in Laravel 9
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_policy_overrides');
    }
};
