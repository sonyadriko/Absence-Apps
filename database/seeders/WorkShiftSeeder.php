<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkShift;
use App\Models\ShiftSlot;
use App\Models\Branch;

class WorkShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🕰️ Creating coffee shop work shifts...');
        
        $shiftTemplates = [
            [
                'name' => 'Pagi (Morning)',
                'code' => 'PAGI',
                'description' => 'Regular morning shift - peak breakfast hours',
                'type' => 'single',
                'total_hours' => 8,
                'is_active' => true,
                'settings' => [
                    'color' => '#ffc107',
                    'requires_manager_approval' => false,
                    'is_default' => true,
                    'peak_hours' => ['07:00-10:00'],
                    'break_duration' => 60
                ]
            ],
            [
                'name' => 'Siang (Afternoon)',
                'code' => 'SIANG',
                'description' => 'Afternoon shift covering lunch and early dinner',
                'type' => 'single',
                'total_hours' => 8,
                'is_active' => true,
                'settings' => [
                    'color' => '#17a2b8',
                    'requires_manager_approval' => false,
                    'is_default' => true,
                    'peak_hours' => ['12:00-14:00', '17:00-19:00'],
                    'break_duration' => 60
                ]
            ],
            [
                'name' => 'Malam (Evening)',
                'code' => 'MALAM',
                'description' => 'Evening shift until late night - dinner and late crowd',
                'type' => 'single',
                'total_hours' => 8,
                'is_active' => true,
                'settings' => [
                    'color' => '#6f42c1',
                    'requires_manager_approval' => false,
                    'is_overnight' => true,
                    'peak_hours' => ['17:00-20:00'],
                    'break_duration' => 60
                ]
            ],
            [
                'name' => 'Split Pagi-Sore',
                'code' => 'SPLIT_PS',
                'description' => 'Split shift: Morning rush + Evening rush with long break',
                'type' => 'split',
                'total_hours' => 8,
                'is_active' => true,
                'settings' => [
                    'color' => '#fd7e14',
                    'requires_manager_approval' => true,
                    'split_break_duration' => 180,
                    'peak_hours' => ['07:00-11:00', '17:00-22:00']
                ]
            ],
            [
                'name' => 'Weekend Full',
                'code' => 'WEEKEND',
                'description' => 'Extended weekend shift with premium pay',
                'type' => 'single',
                'total_hours' => 10,
                'is_active' => true,
                'settings' => [
                    'color' => '#28a745',
                    'requires_manager_approval' => true,
                    'is_weekend_only' => true,
                    'premium_rate' => 1.5,
                    'break_duration' => 120
                ]
            ],
            [
                'name' => 'Express Opening',
                'code' => 'EXPRESS',
                'description' => 'Early opening shift for business districts',
                'type' => 'single',
                'total_hours' => 8,
                'is_active' => true,
                'settings' => [
                    'color' => '#dc3545',
                    'requires_manager_approval' => false,
                    'early_opening' => true,
                    'peak_hours' => ['06:30-09:00'],
                    'break_duration' => 60
                ]
            ]
        ];
        
        foreach ($shiftTemplates as $template) {
            $workShift = WorkShift::create($template);
            
            // Create shift slots for each work shift
            $this->createShiftSlots($workShift);
            
            $this->command->line("  ✓ {$workShift->name} ({$workShift->code})");
        }
        
        $this->command->info("✅ " . count($shiftTemplates) . " work shift templates created!");
        $this->command->info('📅 Each shift has pre-configured time slots and settings');
    }
    
    private function createShiftSlots(WorkShift $workShift)
    {
        $slotDefinitions = [
            'PAGI' => [
                [
                    'name' => 'Morning Shift',
                    'start_time' => '07:00:00',
                    'end_time' => '15:00:00',
                    'order' => 1,
                    'duration_minutes' => 480, // 8 hours
                    'is_overnight' => false,
                    'break_times' => [['start' => '10:00', 'end' => '10:15'], ['start' => '12:00', 'end' => '13:00']]
                ]
            ],
            'SIANG' => [
                [
                    'name' => 'Afternoon Shift',
                    'start_time' => '11:00:00',
                    'end_time' => '19:00:00',
                    'order' => 1,
                    'duration_minutes' => 480,
                    'is_overnight' => false,
                    'break_times' => [['start' => '14:00', 'end' => '14:15'], ['start' => '16:30', 'end' => '17:30']]
                ]
            ],
            'MALAM' => [
                [
                    'name' => 'Evening Shift',
                    'start_time' => '17:00:00',
                    'end_time' => '01:00:00',
                    'order' => 1,
                    'duration_minutes' => 480,
                    'is_overnight' => true,
                    'break_times' => [['start' => '19:00', 'end' => '19:15'], ['start' => '22:00', 'end' => '23:00']]
                ]
            ],
            'SPLIT_PS' => [
                [
                    'name' => 'Morning Session',
                    'start_time' => '07:00:00',
                    'end_time' => '11:00:00',
                    'order' => 1,
                    'duration_minutes' => 240,
                    'is_overnight' => false,
                    'break_times' => [['start' => '09:00', 'end' => '09:15']]
                ],
                [
                    'name' => 'Evening Session',
                    'start_time' => '17:00:00',
                    'end_time' => '21:00:00',
                    'order' => 2,
                    'duration_minutes' => 240,
                    'is_overnight' => false,
                    'break_times' => [['start' => '19:00', 'end' => '19:15']]
                ]
            ],
            'WEEKEND' => [
                [
                    'name' => 'Extended Weekend Shift',
                    'start_time' => '08:00:00',
                    'end_time' => '22:00:00',
                    'order' => 1,
                    'duration_minutes' => 600, // 10 hours
                    'is_overnight' => false,
                    'break_times' => [
                        ['start' => '10:30', 'end' => '10:45'],
                        ['start' => '13:00', 'end' => '14:00'],
                        ['start' => '16:30', 'end' => '16:45'],
                        ['start' => '19:00', 'end' => '20:00']
                    ]
                ]
            ],
            'EXPRESS' => [
                [
                    'name' => 'Express Opening Shift',
                    'start_time' => '06:30:00',
                    'end_time' => '14:30:00',
                    'order' => 1,
                    'duration_minutes' => 480,
                    'is_overnight' => false,
                    'break_times' => [['start' => '09:00', 'end' => '09:15'], ['start' => '11:30', 'end' => '12:30']]
                ]
            ]
        ];
        
        $slots = $slotDefinitions[$workShift->code] ?? [];
        
        foreach ($slots as $slotData) {
            ShiftSlot::create(array_merge($slotData, [
                'work_shift_id' => $workShift->id
            ]));
        }
    }
}
