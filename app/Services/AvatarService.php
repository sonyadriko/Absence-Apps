<?php

namespace App\Services;

class AvatarService
{
    /**
     * Generate avatar URL based on user info
     */
    public static function generateAvatarUrl($user, $size = 36, $style = 'shapes')
    {
        // If user has profile photo, use it
        if ($user->profile_photo && file_exists(storage_path('app/public/' . $user->profile_photo))) {
            return asset('storage/' . $user->profile_photo);
        }

        // Generate unique seed based on user ID and name
        $seed = md5($user->id . $user->name);
        
        // Different avatar styles
        $avatarProviders = [
            'shapes' => "https://api.dicebear.com/7.x/shapes/svg?seed={$seed}&size={$size}",
            'initials' => "https://api.dicebear.com/7.x/initials/svg?seed={$seed}&size={$size}",
            'bottts' => "https://api.dicebear.com/7.x/bottts/svg?seed={$seed}&size={$size}",
            'identicon' => "https://api.dicebear.com/7.x/identicon/svg?seed={$seed}&size={$size}",
            'personas' => "https://api.dicebear.com/7.x/personas/svg?seed={$seed}&size={$size}",
        ];

        return $avatarProviders[$style] ?? $avatarProviders['shapes'];
    }

    /**
     * Get role-based avatar color
     */
    public static function getRoleColors($roleName)
    {
        $colors = [
            'hr_central' => ['bg' => '#6f42c1', 'text' => '#ffffff'],
            'branch_manager' => ['bg' => '#fd7e14', 'text' => '#ffffff'],
            'system_admin' => ['bg' => '#dc3545', 'text' => '#ffffff'],
            'pengelola' => ['bg' => '#20c997', 'text' => '#ffffff'],
            'shift_leader' => ['bg' => '#e83e8c', 'text' => '#ffffff'],
            'supervisor' => ['bg' => '#6610f2', 'text' => '#ffffff'],
            'senior_barista' => ['bg' => '#198754', 'text' => '#ffffff'],
            'employee' => ['bg' => '#0d6efd', 'text' => '#ffffff'],
            'default' => ['bg' => '#6c757d', 'text' => '#ffffff']
        ];

        return $colors[$roleName] ?? $colors['default'];
    }

    /**
     * Get role-based icon
     */
    public static function getRoleIcon($roleName)
    {
        $icons = [
            'hr_central' => 'fas fa-users-cog',
            'branch_manager' => 'fas fa-store',
            'system_admin' => 'fas fa-user-shield',
            'pengelola' => 'fas fa-briefcase',
            'shift_leader' => 'fas fa-user-tie',
            'supervisor' => 'fas fa-clipboard-user',
            'senior_barista' => 'fas fa-coffee',
            'employee' => 'fas fa-user',
            'default' => 'fas fa-user-circle'
        ];

        return $icons[$roleName] ?? $icons['default'];
    }

    /**
     * Create avatar initials from name
     */
    public static function getInitials($name)
    {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    /**
     * Generate gradient background for initials avatar
     */
    public static function generateGradient($seed)
    {
        $hash = md5($seed);
        $color1 = '#' . substr($hash, 0, 6);
        $color2 = '#' . substr($hash, 6, 6);
        
        return "linear-gradient(135deg, {$color1}, {$color2})";
    }
}
