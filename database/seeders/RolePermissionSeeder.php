<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Services\RBACService;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->command->info('🚀 Setting up flexible RBAC system...');

        // First, create all permissions
        $this->createPermissions();

        // Then create roles with their specific configurations
        $this->createRoles();

        $this->command->info('✅ Flexible RBAC system ready! You can now add custom roles anytime.');
    }

    private function createPermissions()
    {
        $this->command->info('Creating permissions...');
        
        $permissions = Permission::generateSystemPermissions();
        
        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }
        
        $this->command->info('✅ ' . count($permissions) . ' permissions created');
    }

    private function createRoles()
    {
        $this->command->info('Creating coffee shop roles...');

        // 1. HR Central - Highest authority (Level 100)
        $hrCentral = $this->createRole([
            'name' => 'hr_central',
            'display_name' => 'HR Central',
            'description' => 'Global HR with access to all branches and full system control',
            'color' => '#dc3545',
            'hierarchy_level' => 100,
            'dashboard_config' => [
                'layout' => 'executive',
                'widgets' => ['global_summary', 'branch_performance', 'pending_approvals', 'system_alerts']
            ],
            'is_system_role' => true
        ]);
        $hrCentral->syncPermissions(Permission::all());

        // 2. Branch Manager - Multi-branch (Level 80)
        $branchManager = $this->createRole([
            'name' => 'branch_manager',
            'display_name' => 'Branch Manager',
            'description' => 'Manager overseeing multiple coffee shop branches',
            'color' => '#ffc107',
            'hierarchy_level' => 80,
            'is_system_role' => true
        ]);
        $branchManager->syncPermissions([
            'branch.view.assigned', 'employee.view.branch', 'attendance.view.branch',
            'schedule.view.branch', 'report.view.branch', 'leave.approve.level2'
        ]);

        // 3. Pengelola - Up to 3 outlets (Level 60)
        $pengelola = $this->createRole([
            'name' => 'pengelola',
            'display_name' => 'Pengelola',
            'description' => 'Daily operations manager for up to 3 coffee shop outlets',
            'color' => '#17a2b8',
            'hierarchy_level' => 60,
            'is_system_role' => true
        ]);
        $pengelola->syncPermissions([
            'branch.view.assigned', 'employee.view.branch', 'attendance.view.branch',
            'schedule.create.branch', 'leave.approve.level1'
        ]);

        // 4. Shift Leader - Team lead (Level 40) - CUSTOMIZABLE
        $this->createRole([
            'name' => 'shift_leader',
            'display_name' => 'Shift Leader',
            'description' => 'Team leader for shift operations - can be customized per branch',
            'color' => '#6f42c1',
            'hierarchy_level' => 40,
            'is_system_role' => false // Can be customized
        ]);

        // 5. Senior Barista - Experienced (Level 30) - CUSTOMIZABLE
        $this->createRole([
            'name' => 'senior_barista',
            'display_name' => 'Senior Barista',
            'description' => 'Experienced barista with mentoring duties - customizable',
            'color' => '#fd7e14',
            'hierarchy_level' => 30,
            'is_system_role' => false
        ]);

        // 6. Employee - Standard (Level 10)
        $employee = $this->createRole([
            'name' => 'employee',
            'display_name' => 'Employee',
            'description' => 'Regular coffee shop staff member',
            'color' => '#28a745',
            'hierarchy_level' => 10,
            'is_system_role' => true
        ]);
        $employee->syncPermissions([
            'attendance.view.own', 'schedule.view.own', 'leave.create.own'
        ]);

        // 7. System Admin - Technical (Level 90)
        $systemAdmin = $this->createRole([
            'name' => 'system_admin',
            'display_name' => 'System Administrator',
            'description' => 'Technical system administrator',
            'color' => '#343a40',
            'hierarchy_level' => 90,
            'is_system_role' => true
        ]);
        $systemAdmin->syncPermissions(Permission::where('group', 'system')->get());

        // 8. Supervisor - Branch supervisor (Level 50) - CUSTOMIZABLE
        $this->createRole([
            'name' => 'supervisor',
            'display_name' => 'Supervisor',
            'description' => 'Branch supervisor - customizable per location',
            'color' => '#20c997',
            'hierarchy_level' => 50,
            'is_system_role' => false
        ]);

        $this->command->info('✅ 8 roles created (5 system + 3 customizable)');
        $this->command->info('💡 Custom roles can be added anytime via admin panel!');
    }

    private function createRole(array $data)
    {
        return Role::updateOrCreate(
            ['name' => $data['name']],
            $data
        );
    }
}
