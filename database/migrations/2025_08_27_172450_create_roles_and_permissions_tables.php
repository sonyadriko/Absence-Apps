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
        // Roles table - dynamic roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'hr_central', 'supervisor', 'shift_leader'
            $table->string('display_name'); // e.g., 'HR Central', 'Supervisor', 'Shift Leader'
            $table->text('description')->nullable();
            $table->string('color')->default('#007bff'); // UI color for role badges
            $table->integer('hierarchy_level')->default(0); // For approval chains (higher = more authority)
            $table->json('dashboard_config')->nullable(); // Custom dashboard configuration
            $table->json('menu_config')->nullable(); // Custom menu configuration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_role')->default(false); // System roles can't be deleted
            $table->timestamps();
        });

        // Permissions table - granular permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'attendance.view.all', 'schedule.create.branch'
            $table->string('display_name'); // e.g., 'View All Attendance', 'Create Branch Schedules'
            $table->text('description')->nullable();
            $table->string('group')->nullable(); // Group permissions (attendance, schedule, reports, etc.)
            $table->string('resource')->nullable(); // Resource type (branch, employee, attendance, etc.)
            $table->string('action')->nullable(); // Action (view, create, edit, delete, approve, etc.)
            $table->string('scope')->nullable(); // Scope (all, branch, own, etc.)
            $table->boolean('is_system_permission')->default(false);
            $table->timestamps();
        });

        // Role-Permission pivot table
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
        });

        // User-Role table (many-to-many for multiple roles per user if needed)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->json('scope_data')->nullable(); // Branch restrictions, date restrictions, etc.
            $table->date('effective_from')->default(now());
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['role_id', 'is_active']);
        });

        // Direct user permissions (for one-off permissions)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->enum('type', ['grant', 'deny'])->default('grant'); // Grant or explicitly deny
            $table->json('scope_data')->nullable();
            $table->date('effective_from')->default(now());
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('granted_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'permission_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
