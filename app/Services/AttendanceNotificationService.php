<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\AttendanceReminderMail;

class AttendanceNotificationService
{
    /**
     * Send check-in reminder
     */
    public function sendCheckInReminder()
    {
        $now = Carbon::now();
        $reminderTime = $now->copy()->subMinutes(30); // 30 mins before shift
        
        // Get employees who should check in soon
        $employees = Employee::with(['schedules' => function ($query) use ($now) {
                $query->active()->forDate($now);
            }])
            ->whereHas('schedules', function ($query) use ($now) {
                $query->active()->forDate($now);
            })
            ->whereDoesntHave('attendances', function ($query) use ($now) {
                $query->where('date', $now->format('Y-m-d'))
                      ->whereNotNull('check_in');
            })
            ->get();
        
        foreach ($employees as $employee) {
            $schedule = $employee->schedules->first();
            if (!$schedule) continue;
            
            $shiftStart = Carbon::parse($schedule->workShift->start_time);
            
            // Check if it's time to send reminder
            if ($now->format('H:i') === $reminderTime->format('H:i')) {
                $this->notifyEmployee($employee, 'check_in_reminder', [
                    'shift_start' => $shiftStart->format('H:i'),
                    'message' => "Your shift starts at {$shiftStart->format('H:i')}. Don't forget to check in!"
                ]);
            }
        }
    }
    
    /**
     * Send check-out reminder
     */
    public function sendCheckOutReminder()
    {
        $now = Carbon::now();
        
        // Get employees who checked in but haven't checked out
        $attendances = Attendance::with(['employee', 'employee.schedules'])
            ->where('date', $now->format('Y-m-d'))
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->get();
        
        foreach ($attendances as $attendance) {
            $employee = $attendance->employee;
            $schedule = $employee->schedules()->active()->forDate($now)->first();
            
            if (!$schedule) continue;
            
            $shiftEnd = Carbon::parse($schedule->workShift->end_time);
            $reminderTime = $shiftEnd->copy()->addMinutes(30);
            
            // Send reminder 30 minutes after shift end
            if ($now->format('H:i') === $reminderTime->format('H:i')) {
                $this->notifyEmployee($employee, 'check_out_reminder', [
                    'check_in_time' => Carbon::parse($attendance->check_in)->format('H:i'),
                    'message' => "You checked in at {$attendance->check_in->format('H:i')} but haven't checked out yet."
                ]);
            }
        }
    }
    
    /**
     * Check for missing attendance
     */
    public function checkMissingAttendance()
    {
        $yesterday = Carbon::yesterday();
        
        // Find incomplete attendance from yesterday
        $incompleteAttendances = Attendance::with('employee')
            ->where('date', $yesterday->format('Y-m-d'))
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->get();
        
        foreach ($incompleteAttendances as $attendance) {
            $this->notifyEmployee($attendance->employee, 'missing_checkout', [
                'date' => $yesterday->format('Y-m-d'),
                'check_in_time' => Carbon::parse($attendance->check_in)->format('H:i'),
                'message' => "You forgot to check out yesterday. Please complete your attendance record."
            ]);
        }
    }
    
    /**
     * Send late arrival notification
     */
    public function notifyLateArrival(Attendance $attendance)
    {
        $employee = $attendance->employee;
        $schedule = $employee->schedules()->active()->forDate($attendance->date)->first();
        
        if (!$schedule) return;
        
        $shiftStart = Carbon::parse($schedule->workShift->start_time);
        $checkIn = Carbon::parse($attendance->check_in);
        $lateMinutes = $checkIn->diffInMinutes($shiftStart);
        
        if ($lateMinutes > $schedule->workShift->grace_period_minutes) {
            $this->notifyEmployee($employee, 'late_arrival', [
                'check_in_time' => $checkIn->format('H:i'),
                'expected_time' => $shiftStart->format('H:i'),
                'late_minutes' => $lateMinutes,
                'message' => "You arrived {$lateMinutes} minutes late today."
            ]);
            
            // Also notify supervisor
            if ($employee->supervisor) {
                $this->notifyEmployee($employee->supervisor, 'employee_late', [
                    'employee_name' => $employee->name,
                    'late_minutes' => $lateMinutes,
                    'message' => "{$employee->name} arrived {$lateMinutes} minutes late today."
                ]);
            }
        }
    }
    
    /**
     * Send weekly summary
     */
    public function sendWeeklySummary()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        $employees = Employee::all();
        
        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->get();
            
            $stats = [
                'total_days' => $attendances->count(),
                'total_hours' => $attendances->sum('total_work_minutes') / 60,
                'late_days' => $attendances->where('late_minutes', '>', 0)->count(),
                'perfect_days' => $attendances->where('late_minutes', 0)
                    ->whereNotNull('check_out')->count()
            ];
            
            $this->notifyEmployee($employee, 'weekly_summary', [
                'week_start' => $startOfWeek->format('Y-m-d'),
                'week_end' => $endOfWeek->format('Y-m-d'),
                'stats' => $stats,
                'message' => "Your weekly attendance summary is ready."
            ]);
        }
    }
    
    /**
     * Helper to send notification
     */
    private function notifyEmployee($employee, $type, $data)
    {
        // Create in-app notification
        Notification::create([
            'user_id' => $employee->user_id,
            'type' => $type,
            'title' => $this->getNotificationTitle($type),
            'message' => $data['message'],
            'data' => json_encode($data),
            'is_read' => false
        ]);
        
        // Send push notification if enabled
        if ($employee->push_enabled && $employee->fcm_token) {
            $this->sendPushNotification($employee->fcm_token, $type, $data);
        }
        
        // Send email if enabled
        if ($employee->email_notifications) {
            Mail::to($employee->user->email)->queue(
                new AttendanceReminderMail($type, $data)
            );
        }
    }
    
    /**
     * Get notification title by type
     */
    private function getNotificationTitle($type)
    {
        $titles = [
            'check_in_reminder' => 'Check-in Reminder',
            'check_out_reminder' => 'Check-out Reminder', 
            'missing_checkout' => 'Missing Checkout Alert',
            'late_arrival' => 'Late Arrival Notice',
            'employee_late' => 'Employee Late Arrival',
            'weekly_summary' => 'Weekly Attendance Summary'
        ];
        
        return $titles[$type] ?? 'Attendance Notification';
    }
    
    /**
     * Send push notification (implement based on your push service)
     */
    private function sendPushNotification($token, $type, $data)
    {
        // Implement push notification logic
        // Could use Firebase Cloud Messaging, OneSignal, etc.
    }
}
