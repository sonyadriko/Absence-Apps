<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('🎆 Starting Coffee Shop Attendance System Setup...');
        $this->command->newLine();
        
        // 1. Setup RBAC system first (roles and permissions)
        $this->call(RolePermissionSeeder::class);
        $this->command->newLine();
        
        // 2. Create positions (needed for employees)
        $this->call(PositionSeeder::class);
        $this->command->newLine();
        
        // 3. Create branches (needed for employee assignments)
        $this->call(BranchSeeder::class);
        $this->command->newLine();
        
        // 3. Create work shifts for each branch
        $this->call(WorkShiftSeeder::class);
        $this->command->newLine();
        
        // 4. Create demo users with proper roles (needs branches to exist)
        $this->call(UserSeeder::class);
        $this->command->newLine();
        
        $this->command->info('🎉 Coffee Shop Attendance System setup completed!');
        $this->command->info('🚀 Ready to serve! You can now login and start managing attendance.');
        $this->command->newLine();
        
        $this->command->warn('📝 Demo Login Credentials:');
        $this->command->line('  • HR Central: hr@coffee.com / password');
        $this->command->line('  • Branch Manager: manager@coffee.com / password');
        $this->command->line('  • Pengelola: pengelola@coffee.com / password');
        $this->command->line('  • Admin: admin@coffee.com / password');
        $this->command->line('  • Employee: employee@coffee.com / password');
        $this->command->newLine();
    }
}
