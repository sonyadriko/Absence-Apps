<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'description' => 'Standard annual leave for all employees',
                'requires_approval' => true,
                'requires_document' => false,
                'max_days_per_year' => 12,
                'is_paid' => true,
                'is_active' => true,
                'settings' => json_encode([
                    'carry_forward_allowed' => true,
                    'max_carry_forward_days' => 5,
                    'advance_notice_days' => 14
                ])
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'description' => 'Medical leave for illness or medical appointments',
                'requires_approval' => true,
                'requires_document' => true,
                'max_days_per_year' => 12,
                'is_paid' => true,
                'is_active' => true,
                'settings' => json_encode([
                    'carry_forward_allowed' => false,
                    'advance_notice_days' => 0,
                    'requires_medical_certificate' => true
                ])
            ],
            [
                'name' => 'Personal Leave',
                'code' => 'personal',
                'description' => 'Personal leave for family matters or personal business',
                'requires_approval' => true,
                'requires_document' => false,
                'max_days_per_year' => 5,
                'is_paid' => false,
                'is_active' => true,
                'settings' => json_encode([
                    'carry_forward_allowed' => false,
                    'advance_notice_days' => 7
                ])
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'maternity',
                'description' => 'Maternity leave for new mothers',
                'requires_approval' => true,
                'requires_document' => true,
                'max_days_per_year' => 90,
                'is_paid' => true,
                'is_active' => true,
                'settings' => json_encode([
                    'carry_forward_allowed' => false,
                    'advance_notice_days' => 30,
                    'gender_specific' => 'female'
                ])
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'paternity',
                'description' => 'Paternity leave for new fathers',
                'requires_approval' => true,
                'requires_document' => true,
                'max_days_per_year' => 3,
                'is_paid' => true,
                'is_active' => true,
                'settings' => json_encode([
                    'carry_forward_allowed' => false,
                    'advance_notice_days' => 14,
                    'gender_specific' => 'male'
                ])
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'emergency',
                'description' => 'Emergency leave for urgent situations',
                'requires_approval' => true,
                'requires_document' => false,
                'max_days_per_year' => null,
                'is_paid' => false,
                'is_active' => true,
                'settings' => json_encode([
                    'carry_forward_allowed' => false,
                    'advance_notice_days' => 0
                ])
            ]
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                ['code' => $leaveType['code']], 
                $leaveType
            );
        }
    }
}
