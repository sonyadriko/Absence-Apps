<?php

namespace App\Services;

use App\Models\AttendancePolicy;
use App\Models\AttendancePolicyOverride;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Position;
use Carbon\Carbon;

class PolicyResolver
{
    /**
     * Resolve the effective attendance policy for a given context
     * Hierarchy: Employee-specific > Position-specific > Branch-specific > Default
     */
    public function resolvePolicy($employee, $branch = null, $date = null)
    {
        $date = $date ?? Carbon::today();
        $branch = $branch ?? $employee->primaryBranch;
        
        // Get the default policy
        $defaultPolicy = AttendancePolicy::where('is_default', true)
            ->where('is_active', true)
            ->first();
            
        if (!$defaultPolicy) {
            throw new \Exception('No default attendance policy found');
        }
        
        $policyData = $defaultPolicy->toArray();
        
        // Apply overrides in hierarchy order
        $this->applyBranchOverrides($policyData, $defaultPolicy->id, $branch, $date);
        $this->applyPositionOverrides($policyData, $defaultPolicy->id, $employee->position, $date);
        $this->applyEmployeeOverrides($policyData, $defaultPolicy->id, $employee, $date);
        
        return $policyData;
    }
    
    /**
     * Get specific policy value with override resolution
     */
    public function getPolicyValue($field, $employee, $branch = null, $date = null)
    {
        $policy = $this->resolvePolicy($employee, $branch, $date);
        return $policy[$field] ?? null;
    }
    
    /**
     * Apply branch-level overrides
     */
    private function applyBranchOverrides(&$policyData, $policyId, $branch, $date)
    {
        if (!$branch) return;
        
        $overrides = AttendancePolicyOverride::where('attendance_policy_id', $policyId)
            ->where('branch_id', $branch->id)
            ->whereNull('position_id')
            ->whereNull('employee_id')
            ->where('is_active', true)
            ->where(function($query) use ($date) {
                $query->whereNull('effective_from')
                      ->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function($query) use ($date) {
                $query->whereNull('effective_until')
                      ->orWhereDate('effective_until', '>=', $date);
            })
            ->get();
            
        $this->applyOverridesToPolicy($policyData, $overrides);
    }
    
    /**
     * Apply position-level overrides
     */
    private function applyPositionOverrides(&$policyData, $policyId, $position, $date)
    {
        if (!$position) return;
        
        $overrides = AttendancePolicyOverride::where('attendance_policy_id', $policyId)
            ->where('position_id', $position->id)
            ->whereNull('branch_id')
            ->whereNull('employee_id')
            ->where('is_active', true)
            ->where(function($query) use ($date) {
                $query->whereNull('effective_from')
                      ->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function($query) use ($date) {
                $query->whereNull('effective_until')
                      ->orWhereDate('effective_until', '>=', $date);
            })
            ->get();
            
        $this->applyOverridesToPolicy($policyData, $overrides);
    }
    
    /**
     * Apply employee-specific overrides
     */
    private function applyEmployeeOverrides(&$policyData, $policyId, $employee, $date)
    {
        $overrides = AttendancePolicyOverride::where('attendance_policy_id', $policyId)
            ->where('employee_id', $employee->id)
            ->whereNull('branch_id')
            ->whereNull('position_id')
            ->where('is_active', true)
            ->where(function($query) use ($date) {
                $query->whereNull('effective_from')
                      ->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function($query) use ($date) {
                $query->whereNull('effective_until')
                      ->orWhereDate('effective_until', '>=', $date);
            })
            ->get();
            
        $this->applyOverridesToPolicy($policyData, $overrides);
    }
    
    /**
     * Apply a collection of overrides to policy data
     */
    private function applyOverridesToPolicy(&$policyData, $overrides)
    {
        foreach ($overrides as $override) {
            $field = $override->override_field;
            $value = $this->castOverrideValue($field, $override->override_value);
            $policyData[$field] = $value;
        }
    }
    
    /**
     * Cast override value to appropriate type based on field
     */
    private function castOverrideValue($field, $value)
    {
        // Boolean fields
        $booleanFields = [
            'geofence_required', 'selfie_required', 'auto_overtime_calculation',
            'enforce_break_times', 'send_late_notifications', 
            'send_missing_checkout_notifications', 'is_active'
        ];
        
        // Integer fields
        $integerFields = [
            'grace_late_min', 'grace_early_leave_min', 'flexible_start_window_min',
            'geofence_radius_meters', 'min_work_minutes_for_present',
            'overtime_threshold_minutes', 'max_break_duration_minutes'
        ];
        
        // Decimal fields
        $decimalFields = ['min_face_confidence'];
        
        // JSON/Array fields
        $arrayFields = ['event_priority'];
        
        if (in_array($field, $booleanFields)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        if (in_array($field, $integerFields)) {
            return (int) $value;
        }
        
        if (in_array($field, $decimalFields)) {
            return (float) $value;
        }
        
        if (in_array($field, $arrayFields)) {
            return is_string($value) ? json_decode($value, true) : $value;
        }
        
        return $value; // Return as-is for other fields
    }
    
    /**
     * Check if an employee is exempt from specific policy rules
     */
    public function isExemptFromRule($employee, $rule, $branch = null, $date = null)
    {
        $policy = $this->resolvePolicy($employee, $branch, $date);
        
        switch ($rule) {
            case 'geofence':
                return !$policy['geofence_required'];
            case 'selfie':
                return !$policy['selfie_required'];
            case 'break_enforcement':
                return !$policy['enforce_break_times'];
            default:
                return false;
        }
    }
    
    /**
     * Get event source priority for a given context
     */
    public function getEventSourcePriority($employee, $branch = null, $date = null)
    {
        $policy = $this->resolvePolicy($employee, $branch, $date);
        return $policy['event_priority'] ?? ['fp_device', 'kiosk', 'mobile', 'web'];
    }
}
