<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💼 Creating employee positions...');
        
        $positions = [
            ['name' => 'HR Manager', 'description' => 'Human Resources Manager'],
            ['name' => 'Branch Manager', 'description' => 'Branch Operations Manager'],
            ['name' => 'Pengelola', 'description' => 'Outlet Manager'],
            ['name' => 'System Administrator', 'description' => 'IT System Administrator'],
            ['name' => 'Shift Leader', 'description' => 'Team Shift Leader'],
            ['name' => 'Supervisor', 'description' => 'Operations Supervisor'],
            ['name' => 'Barista', 'description' => 'Coffee Shop Barista'],
            ['name' => 'Senior Barista', 'description' => 'Senior Coffee Specialist'],
            ['name' => 'Cashier', 'description' => 'Point of Sale Operator'],
            ['name' => 'Kitchen Staff', 'description' => 'Food Preparation Staff']
        ];
        
        foreach ($positions as $position) {
            Position::create($position);
        }
        
        $this->command->info('✅ ' . count($positions) . ' positions created!');
    }
}
