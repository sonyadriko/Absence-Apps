<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;

class FixAttendanceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:fix-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix attendance records with null check-in/out times';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix attendance data...');
        
        // Find records with status 'present' but null check_in_time
        $brokenRecords = Attendance::where('status', 'present')
            ->whereNull('check_in_time')
            ->get();
            
        $this->info('Found ' . $brokenRecords->count() . ' broken records');
        
        foreach ($brokenRecords as $record) {
            // Delete records that have no check-in or check-out times
            if (is_null($record->check_in_time) && is_null($record->check_out_time)) {
                $this->info('Deleting record ID: ' . $record->id . ' for date: ' . $record->date);
                $record->delete();
            }
        }
        
        $this->info('Attendance data cleanup completed!');
    }
}
