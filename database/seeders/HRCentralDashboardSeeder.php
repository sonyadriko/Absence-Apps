<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;

class HRCentralDashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Indonesian locale
        
        $this->command->info('🚀 Starting HR Central Dashboard Seeder...');
        
        // 1. Skip creating branches - they already exist
        // $this->createBranches($faker);
        
        // 2. Create employees and users
        $this->createEmployeesAndUsers($faker);
        
        // 3. Create attendance records for the last 3 months
        $this->createAttendanceRecords($faker);
        
        // 4. Create leave requests
        $this->createLeaveRequests($faker);
        
        $this->command->info('✅ HR Central Dashboard Seeder completed successfully!');
    }

    private function createBranches($faker)
    {
        $this->command->info('📍 Creating branches...');
        
        $cities = [
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 
            'Makassar', 'Palembang', 'Denpasar', 'Yogyakarta', 'Solo',
            'Malang', 'Bogor', 'Tangerang', 'Bekasi', 'Depok'
        ];
        
        $locations = [
            'Mall Central', 'Plaza Indonesia', 'Grand Indonesia', 'Mall Kelapa Gading',
            'Mall Taman Anggrek', 'Senayan City', 'Pacific Place', 'Kuningan City',
            'Mall Ambassador', 'Mal Ciputra', 'Town Square', 'Mall Olympic Garden',
            'Mal Artha Gading', 'Mall Lippo', 'Central Park'
        ];

        foreach ($cities as $index => $city) {
            if ($index < count($locations)) {
                Branch::create([
                    'name' => "Coffee Central - {$locations[$index]}",
                    'code' => 'CC' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'address' => $faker->address . ', ' . $city,
                    'latitude' => $faker->randomFloat(6, -10, 10), // Indonesia range approx
                    'longitude' => $faker->randomFloat(6, 95, 141), // Indonesia range approx
                    'radius' => rand(50, 200), // Geofence radius in meters
                    'phone' => $faker->phoneNumber,
                    'is_active' => $faker->boolean(90), // 90% chance of being active
                    'created_at' => Carbon::now()->subDays(rand(30, 365)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 30))
                ]);
            }
        }
        
        $this->command->info("✅ Created " . Branch::count() . " branches");
    }

    private function createEmployeesAndUsers($faker)
    {
        $this->command->info('👥 Creating employees and users...');
        
        $branches = Branch::all();
        $roles = Role::all();
        
        // Indonesian names
        $firstNames = [
            'Ahmad', 'Sari', 'Budi', 'Dewi', 'Andi', 'Rina', 'Joko', 'Maya', 'Agus', 'Lina',
            'Dedi', 'Novi', 'Rudi', 'Indira', 'Hendra', 'Fitri', 'Bambang', 'Sri', 'Wawan', 'Tuti',
            'Iwan', 'Sinta', 'Dony', 'Ratna', 'Eko', 'Yuni', 'Feri', 'Diah', 'Rizki', 'Ayu',
            'Adi', 'Nisa', 'Roni', 'Mega', 'Dani', 'Sella', 'Fajar', 'Lia', 'Bayu', 'Vina'
        ];
        
        $lastNames = [
            'Pratama', 'Sari', 'Wijaya', 'Putri', 'Santoso', 'Wati', 'Setiawan', 'Dewi',
            'Kurniawan', 'Rahayu', 'Purwanto', 'Indah', 'Gunawan', 'Safitri', 'Suryadi', 'Maharani',
            'Wibowo', 'Lestari', 'Nugroho', 'Anggraini', 'Hidayat', 'Permata', 'Susanto', 'Kartika'
        ];

        $positions = [
            'Barista', 'Senior Barista', 'Shift Leader', 'Store Supervisor', 
            'Assistant Manager', 'Store Manager', 'Area Manager', 'Regional Manager'
        ];

        // Create 60 employees
        for ($i = 0; $i < 60; $i++) {
            $firstName = $faker->randomElement($firstNames);
            $lastName = $faker->randomElement($lastNames);
            $fullName = $firstName . ' ' . $lastName;
            
            $branch = $faker->randomElement($branches);
            $position = $faker->randomElement($positions);
            
            // Get position ID from database
            $positionName = match($position) {
                'Barista' => 'Barista',
                'Senior Barista' => 'Senior Barista',
                'Shift Leader' => 'Shift Leader',
                'Store Supervisor' => 'Supervisor',
                'Assistant Manager' => 'Branch Manager',
                'Store Manager' => 'Branch Manager', 
                'Area Manager' => 'Pengelola',
                'Regional Manager' => 'HR Manager',
                default => 'Barista'
            };
            
            $positionRecord = DB::table('positions')->where('name', $positionName)->first();
            $positionId = $positionRecord ? $positionRecord->id : 7; // Default to Barista position

            // Create User first
            $user = User::create([
                'name' => $fullName,
                'email' => strtolower($firstName . '.' . $lastName . $i) . '@coffeecentral.id',
                'password' => Hash::make('password123'),
                'is_active' => $faker->boolean(95), // 95% active
                'created_at' => Carbon::now()->subDays(rand(30, 365)),
                'last_login_at' => $faker->boolean(70) ? Carbon::now()->subDays(rand(0, 7)) : null,
            ]);

            // Create Employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_number' => 'EMP' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'full_name' => $fullName,
                'email' => $user->email,
                'phone' => $faker->phoneNumber,
                'position_id' => $positionId,
                'primary_branch_id' => $branch->id,
                'hire_date' => Carbon::now()->subDays(rand(30, 1095)), // 1 month to 3 years
                'status' => $faker->randomElement(['active', 'active', 'active', 'active', 'inactive']), // 80% active
                'employment_type' => $faker->randomElement(['full_time', 'part_time', 'contract']),
                'hourly_rate' => rand(25000, 75000), // IDR 25k - 75k per hour
                'department' => $faker->randomElement(['Operations', 'Kitchen', 'Service', 'Management']),
                'address' => $faker->address,
                'emergency_contact_name' => $faker->name,
                'emergency_contact_phone' => $faker->phoneNumber,
            ]);

            // Assign role based on position
            $roleName = match($position) {
                'Barista' => 'employee',
                'Senior Barista' => 'senior_barista',
                'Shift Leader' => 'shift_leader', 
                'Store Supervisor' => 'supervisor',
                'Assistant Manager' => 'branch_manager',
                'Store Manager' => 'branch_manager',
                'Area Manager' => 'pengelola',
                'Regional Manager' => 'hr_central',
                default => 'employee'
            };

            $role = Role::where('name', $roleName)->first();
            if ($role) {
                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'is_active' => true,
                    'effective_from' => $user->created_at,
                ]);
            }
        }
        
        $this->command->info("✅ Created " . User::count() . " users and " . Employee::count() . " employees");
    }

    private function createAttendanceRecords($faker)
    {
        $this->command->info('⏰ Creating attendance records for last 3 months...');
        
        $employees = Employee::where('status', 'active')->get();
        $startDate = Carbon::now()->subMonths(3);
        $endDate = Carbon::now();
        
        $attendanceCount = 0;
        
        foreach ($employees as $employee) {
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                // Skip weekends (Saturday = 6, Sunday = 0)
                if ($currentDate->dayOfWeek != 0 && $currentDate->dayOfWeek != 6) {
                    
                    // 85% chance of attendance
                    if ($faker->boolean(85)) {
                        $checkInHour = $faker->numberBetween(7, 9); // 7-9 AM
                        $checkInMinute = $faker->numberBetween(0, 59);
                        $checkIn = $currentDate->copy()->setTime($checkInHour, $checkInMinute);
                        
                        $checkOutHour = $faker->numberBetween(17, 20); // 5-8 PM  
                        $checkOutMinute = $faker->numberBetween(0, 59);
                        $checkOut = $currentDate->copy()->setTime($checkOutHour, $checkOutMinute);
                        
                        $scheduledStart = $currentDate->copy()->setTime(8, 0); // 8 AM
                        $scheduledEnd = $currentDate->copy()->setTime(17, 0); // 5 PM
                        
                        $lateMinutes = max(0, $checkIn->diffInMinutes($scheduledStart, false));
                        $earlyMinutes = max(0, $scheduledEnd->diffInMinutes($checkOut, false));
                        $totalWorkMinutes = $checkIn->diffInMinutes($checkOut);
                        
                        Attendance::create([
                            'employee_id' => $employee->id,
                            'branch_id' => $employee->primary_branch_id,
                            'date' => $currentDate->format('Y-m-d'),
                            'check_in' => $checkIn,
                            'check_out' => $checkOut,
                            'scheduled_start' => $scheduledStart,
                            'scheduled_end' => $scheduledEnd,
                            'actual_check_in' => $checkIn,
                            'actual_check_out' => $checkOut,
                            'status' => $lateMinutes > 15 ? 'late' : 'present',
                            'late_minutes' => $lateMinutes > 0 ? $lateMinutes : 0,
                            'early_minutes' => $earlyMinutes > 0 ? $earlyMinutes : 0,
                            'total_work_minutes' => $totalWorkMinutes,
                            'overtime_minutes' => max(0, $totalWorkMinutes - 480), // More than 8 hours
                            'notes' => $lateMinutes > 30 ? $faker->sentence : null,
                            'last_computed_at' => Carbon::now(),
                            'computation_version' => '1.0'
                        ]);
                        
                        $attendanceCount++;
                    }
                }
                
                $currentDate->addDay();
            }
        }
        
        $this->command->info("✅ Created {$attendanceCount} attendance records");
    }

    private function createLeaveRequests($faker)
    {
        $this->command->info('🏖️ Creating leave requests...');
        
        $employees = Employee::where('status', 'active')->limit(30)->get(); // Only 30 employees have leave requests
        $leaveTypes = DB::table('leave_types')->get(); // Get actual leave types from database
        $approvalStatuses = ['pending', 'approved_by_pengelola', 'approved_by_manager', 'approved_by_hr', 'approved', 'rejected'];
        
        $leaveCount = 0;
        
        foreach ($employees as $employee) {
            // Each employee has 1-4 leave requests
            $requestCount = rand(1, 4);
            
            for ($i = 0; $i < $requestCount; $i++) {
                $leaveType = $faker->randomElement($leaveTypes);
                $status = $faker->randomElement($approvalStatuses);
                
                // Generate random dates within last 6 months
                $startDate = Carbon::now()->subMonths(6)->addDays(rand(0, 180));
                $duration = match($leaveType->code) {
                    'annual' => rand(1, 7),
                    'sick' => rand(1, 3), 
                    'personal' => rand(1, 2),
                    'maternity' => 90, // Fixed 90 days
                    'paternity' => 3, // Fixed 3 days
                    'emergency' => 1, // Fixed 1 day
                    default => rand(1, 3)
                };
                
                $endDate = $startDate->copy()->addDays($duration - 1);
                
                $leaveRequest = LeaveRequest::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_days' => $duration,
                    'reason' => $this->getLeaveReason($leaveType->code, $faker),
                    'status' => $status,
                    'document_path' => $faker->boolean(30) ? 'documents/leave/doc-' . $faker->numberBetween(1000, 9999) . '.pdf' : null,
                    'created_at' => $startDate->copy()->subDays(rand(1, 14)), // Requested 1-14 days before start
                ]);

                // Add approval data for approved/rejected requests
                if (in_array($status, ['approved', 'rejected'])) {
                    $approvalDate = $leaveRequest->created_at->addDays(rand(1, 5));
                    
                    if (in_array($status, ['approved_by_pengelola', 'approved_by_manager', 'approved_by_hr', 'approved'])) {
                        $leaveRequest->update([
                            'pengelola_approved_by' => rand(1, 5),
                            'pengelola_approved_at' => $approvalDate,
                            'pengelola_notes' => 'Approved by supervisor',
                            'manager_approved_by' => rand(1, 5),
                            'manager_approved_at' => $approvalDate->copy()->addHours(rand(2, 24)),
                            'manager_notes' => 'Leave approved',
                        ]);
                    } else {
                        $leaveRequest->update([
                            'rejected_by' => rand(1, 5),
                            'rejected_at' => $approvalDate,
                            'rejection_reason' => $faker->sentence,
                        ]);
                    }
                }
                
                $leaveCount++;
            }
        }
        
        $this->command->info("✅ Created {$leaveCount} leave requests");
    }

    private function getLeaveReason($leaveTypeCode, $faker)
    {
        return match($leaveTypeCode) {
            'annual' => $faker->randomElement([
                'Family vacation to Bali',
                'Wedding ceremony preparation',
                'Annual family gathering',
                'Rest and relaxation',
                'Personal time with family'
            ]),
            'sick' => $faker->randomElement([
                'Flu and fever',
                'Food poisoning',
                'Migraine headache', 
                'Medical check-up',
                'Recovery from illness'
            ]),
            'personal' => $faker->randomElement([
                'Family emergency',
                'House moving',
                'Personal appointment',
                'Taking care of elderly parent',
                'Personal matters'
            ]),
            'maternity' => 'Maternity leave for childbirth',
            'paternity' => 'Paternity leave for newborn care',
            'emergency' => $faker->randomElement([
                'Family member hospitalized',
                'House flood damage',
                'Vehicle accident',
                'Urgent family matter'
            ]),
            default => $faker->sentence
        };
    }
}
