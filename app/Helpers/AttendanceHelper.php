<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;

class AttendanceHelper
{
    /**
     * Store attendance selfie with optimization
     */
    public static function storeSelfie(UploadedFile $file, int $employeeId, string $type = 'check_in'): string
    {
        // Generate unique filename
        $date = Carbon::now()->format('Y-m-d');
        $time = Carbon::now()->format('His');
        $filename = "attendance/{$employeeId}/{$date}/{$type}_{$time}.jpg";
        
        // Process image
        $image = Image::make($file);
        
        // Resize if too large (max 800x800)
        $image->resize(800, 800, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Add watermark with timestamp
        $image->text(Carbon::now()->format('Y-m-d H:i:s'), 10, $image->height() - 10, function($font) {
            $font->size(14);
            $font->color('#ffffff');
            $font->align('left');
            $font->valign('bottom');
        });
        
        // Optimize quality
        $image->encode('jpg', 85);
        
        // Store to disk
        Storage::disk('public')->put($filename, $image->stream());
        
        return $filename;
    }
    
    /**
     * Calculate distance between two coordinates in meters
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);
        
        $a = sin($deltaLat/2) * sin($deltaLat/2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon/2) * sin($deltaLon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Validate if location is within geofence
     */
    public static function isWithinGeofence(float $lat, float $lon, $branch): array
    {
        if (!$branch || !$branch->latitude || !$branch->longitude) {
            return [
                'valid' => false,
                'distance' => null,
                'message' => 'Branch location not configured'
            ];
        }
        
        $distance = self::calculateDistance(
            $lat, $lon,
            $branch->latitude, $branch->longitude
        );
        
        $radius = $branch->geofence_radius ?? 100; // default 100 meters
        $isValid = $distance <= $radius;
        
        return [
            'valid' => $isValid,
            'distance' => round($distance, 2),
            'radius' => $radius,
            'message' => $isValid 
                ? "You are within the allowed area ({$distance}m from office)"
                : "You are too far from office ({$distance}m, max: {$radius}m)"
        ];
    }
    
    /**
     * Detect late check-in based on shift schedule
     */
    public static function detectLateCheckIn($checkInTime, $shiftStart, int $gracePeriodMinutes = 15): array
    {
        $checkIn = Carbon::parse($checkInTime);
        $shiftStartTime = Carbon::parse($shiftStart);
        
        $diffMinutes = $checkIn->diffInMinutes($shiftStartTime, false);
        $isLate = $diffMinutes > $gracePeriodMinutes;
        
        return [
            'is_late' => $isLate,
            'late_minutes' => max(0, $diffMinutes - $gracePeriodMinutes),
            'check_in_time' => $checkIn->format('H:i:s'),
            'expected_time' => $shiftStartTime->format('H:i:s'),
            'grace_period' => $gracePeriodMinutes
        ];
    }
    
    /**
     * Calculate overtime
     */
    public static function calculateOvertime($checkOutTime, $shiftEnd, int $minOvertimeMinutes = 30): array
    {
        $checkOut = Carbon::parse($checkOutTime);
        $shiftEndTime = Carbon::parse($shiftEnd);
        
        $diffMinutes = $checkOut->diffInMinutes($shiftEndTime, false);
        $hasOvertime = $diffMinutes >= $minOvertimeMinutes;
        
        return [
            'has_overtime' => $hasOvertime,
            'overtime_minutes' => $hasOvertime ? $diffMinutes : 0,
            'overtime_hours' => $hasOvertime ? round($diffMinutes / 60, 2) : 0,
            'check_out_time' => $checkOut->format('H:i:s'),
            'expected_end' => $shiftEndTime->format('H:i:s')
        ];
    }
    
    /**
     * Generate attendance summary
     */
    public static function generateDailySummary($attendance): array
    {
        if (!$attendance->check_in || !$attendance->check_out) {
            return [
                'status' => 'incomplete',
                'work_hours' => 0,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
                'is_complete' => false
            ];
        }
        
        $checkIn = Carbon::parse($attendance->check_in);
        $checkOut = Carbon::parse($attendance->check_out);
        $workMinutes = $checkIn->diffInMinutes($checkOut);
        
        return [
            'status' => 'complete',
            'work_hours' => round($workMinutes / 60, 2),
            'work_duration' => self::formatDuration($workMinutes),
            'late_minutes' => $attendance->late_minutes ?? 0,
            'overtime_minutes' => $attendance->overtime_minutes ?? 0,
            'is_complete' => true,
            'productivity_score' => self::calculateProductivityScore($attendance)
        ];
    }
    
    /**
     * Format duration in human readable format
     */
    public static function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}m";
        }
    }
    
    /**
     * Calculate productivity score (simplified)
     */
    private static function calculateProductivityScore($attendance): int
    {
        $score = 100;
        
        // Deduct for late check-in
        if ($attendance->late_minutes > 0) {
            $score -= min(20, floor($attendance->late_minutes / 5) * 2);
        }
        
        // Deduct for early checkout
        if ($attendance->early_minutes > 0) {
            $score -= min(20, floor($attendance->early_minutes / 5) * 2);
        }
        
        // Bonus for overtime
        if ($attendance->overtime_minutes > 60) {
            $score += min(10, floor($attendance->overtime_minutes / 60));
        }
        
        return max(0, min(100, $score));
    }
}
