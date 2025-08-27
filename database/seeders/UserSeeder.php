<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Role;
use App\Services\RBACService;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    protected $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Creating demo users for each role...');
        
        // Demo users for testing
        $users = [
            [
                'name' => 'HR Central Admin',
                'email' => 'hr@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'hr_central',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'HR001',
                    'full_name' => 'HR Central Admin',
                    'email' => 'hr@coffee.com',
                    'phone' => '+62812345671',
                    'hire_date' => now()->subYears(2),
                    'status' => 'active',
                    'position_id' => 1,
                    'department' => 'Human Resources'
                ],
                'rbac_role' => 'hr_central'
            ],
            [
                'name' => 'Branch Manager',
                'email' => 'manager@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'branch_manager',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'BM001',
                    'full_name' => 'Branch Manager',
                    'email' => 'manager@coffee.com',
                    'phone' => '+62812345672',
                    'hire_date' => now()->subYears(1),
                    'status' => 'active',
                    'position_id' => 2,
                    'department' => 'Operations',
                    'primary_branch_id' => 1
                ],
                'rbac_role' => 'branch_manager',
                'scope_data' => [
                    'branches' => [1, 2]
                ]
            ],
            [
                'name' => 'Pengelola Coffee',
                'email' => 'pengelola@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'pengelola',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'PG001',
                    'full_name' => 'Pengelola Coffee',
                    'email' => 'pengelola@coffee.com',
                    'phone' => '+62812345673',
                    'hire_date' => now()->subMonths(8),
                    'status' => 'active',
                    'position_id' => 3,
                    'department' => 'Operations',
                    'primary_branch_id' => 1
                ],
                'rbac_role' => 'pengelola',
                'scope_data' => [
                    'branches' => [1, 3, 4]
                ]
            ],
            [
                'name' => 'System Admin',
                'email' => 'admin@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'system_admin',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'SA001',
                    'full_name' => 'System Admin',
                    'email' => 'admin@coffee.com',
                    'phone' => '+62812345674',
                    'hire_date' => now()->subYears(3),
                    'status' => 'active',
                    'position_id' => 4,
                    'department' => 'IT'
                ],
                'rbac_role' => 'system_admin'
            ],
            [
                'name' => 'Shift Leader',
                'email' => 'shift.leader@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'shift_leader',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'SL001',
                    'full_name' => 'Shift Leader Morning',
                    'email' => 'shift.leader@coffee.com',
                    'phone' => '+62812345675',
                    'hire_date' => now()->subMonths(6),
                    'status' => 'active',
                    'position_id' => 5,
                    'department' => 'Operations',
                    'primary_branch_id' => 1
                ],
                'rbac_role' => 'shift_leader',
                'scope_data' => [
                    'branches' => [1]
                ]
            ],
            [
                'name' => 'Supervisor',
                'email' => 'supervisor@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'SV001',
                    'full_name' => 'Supervisor Central',
                    'email' => 'supervisor@coffee.com',
                    'phone' => '+62812345676',
                    'hire_date' => now()->subMonths(4),
                    'status' => 'active',
                    'position_id' => 6,
                    'department' => 'Operations',
                    'primary_branch_id' => 1
                ],
                'rbac_role' => 'supervisor',
                'scope_data' => [
                    'branches' => [1]
                ]
            ],
            [
                'name' => 'Regular Employee',
                'email' => 'employee@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'EMP001',
                    'full_name' => 'Regular Employee',
                    'email' => 'employee@coffee.com',
                    'phone' => '+62812345677',
                    'hire_date' => now()->subMonths(3),
                    'status' => 'active',
                    'position_id' => 7,
                    'department' => 'Service',
                    'primary_branch_id' => 1
                ],
                'rbac_role' => 'employee'
            ],
            [
                'name' => 'Senior Barista',
                'email' => 'barista@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'senior_barista',
                'is_active' => true,
                'employee_data' => [
                    'employee_number' => 'SB001',
                    'full_name' => 'Senior Barista',
                    'email' => 'barista@coffee.com',
                    'phone' => '+62812345678',
                    'hire_date' => now()->subMonths(10),
                    'status' => 'active',
                    'position_id' => 8,
                    'department' => 'Service',
                    'primary_branch_id' => 1
                ],
                'rbac_role' => 'senior_barista',
                'scope_data' => [
                    'branches' => [1]
                ]
            ]
        ];

        foreach ($users as $userData) {
            $this->createUserWithRole($userData);
        }

        $this->command->info('✅ ' . count($users) . ' demo users created!');
        $this->command->info('🔑 All users have password: "password"');
    }

    private function createUserWithRole(array $userData)
    {
        // Create user
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'role' => $userData['role'], // Keep old role field for backward compatibility
            'is_active' => $userData['is_active'],
            'email_verified_at' => now()
        ]);

        // Create employee record if provided
        if (isset($userData['employee_data'])) {
            $employeeData = $userData['employee_data'];
            $employeeData['user_id'] = $user->id;
            
            Employee::create($employeeData);
        }

        // Assign RBAC role if specified
        if (isset($userData['rbac_role'])) {
            $role = Role::where('name', $userData['rbac_role'])->first();
            if ($role) {
                $scopeData = $userData['scope_data'] ?? [];
                $this->rbacService->assignRole($user, $role, $scopeData);
                
                $this->command->line("  ✓ User: {$user->email} → Role: {$role->display_name}");
            }
        }

        return $user;
    }
}
