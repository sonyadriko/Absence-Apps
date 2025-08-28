<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

// Find employee user
$user = User::where('email', 'employee@coffee.com')->first();

if (!$user || !$user->employee) {
    die("Employee user not found\n");
}

$employeeId = $user->employee->id;

// Clear existing attendance for this month
Attendance::where('employee_id', $employeeId)
    ->whereMonth('date', Carbon::now()->month)
    ->whereYear('date', Carbon::now()->year)
    ->delete();

echo "Creating test attendance data...\n";

// Create various attendance records for current month
$currentMonth = Carbon::now()->startOfMonth();
$today = Carbon::now();

for ($i = 0; $i < 20; $i++) {
    $date = $currentMonth->copy()->addDays($i);
    
    // Skip weekends
    if ($date->isWeekend()) continue;
    
    // Skip future dates
    if ($date->gt($today)) break;
    
    // Determine status randomly
    $statuses = ['present', 'present', 'present', 'late', 'late']; // More present than late
    $status = $statuses[array_rand($statuses)];
    
    // Set check-in time based on status
    if ($status === 'late') {
        $checkInTime = $date->copy()->setTime(rand(9, 10), rand(1, 59), 0);
        $lateMinutes = $checkInTime->diffInMinutes($date->copy()->setTime(9, 0, 0));
    } else {
        $checkInTime = $date->copy()->setTime(rand(7, 8), rand(0, 59), 0);
        $lateMinutes = 0;
    }
    
    // Set check-out time (some might not have check-out for recent dates)
    $checkOutTime = null;
    $totalMinutes = 0;
    
    if ($date->lt($today) || rand(0, 1)) { // Past dates always have checkout
        $checkOutTime = $date->copy()->setTime(rand(17, 19), rand(0, 59), 0);
        $totalMinutes = $checkInTime->diffInMinutes($checkOutTime);
    }
    
    Attendance::create([
        'employee_id' => $employeeId,
        'date' => $date->format('Y-m-d'),
        'status' => $status,
        'check_in' => $checkInTime,
        'check_out' => $checkOutTime,
        'actual_check_in' => $checkInTime,
        'actual_check_out' => $checkOutTime,
        'late_minutes' => $lateMinutes,
        'total_work_minutes' => $totalMinutes,
        'branch_id' => 1,
        'notes' => $status === 'late' ? 'Traffic jam' : null,
        'location_data' => json_encode([
            'check_in' => [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'time' => $checkInTime->toISOString()
            ]
        ])
    ]);
    
    echo "Created {$status} attendance for {$date->format('Y-m-d')}\n";
}

echo "\nTest data created successfully!\n";
echo "Present days: " . Attendance::where('employee_id', $employeeId)->where('status', 'present')->count() . "\n";
echo "Late days: " . Attendance::where('employee_id', $employeeId)->where('status', 'late')->count() . "\n";
