<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('☕ Creating coffee shop branches...');
        
        $branches = [
            [
                'name' => 'Coffee Central - Mall Jakarta',
                'code' => 'CC-MJK',
                'address' => 'Mall Jakarta, Level 2, Unit 201-202, Jakarta, DKI Jakarta 12560',
                'phone' => '+62-21-7890123',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 50, // 50 meters
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'operating_hours' => [
                    'monday' => ['open' => '07:00', 'close' => '22:00'],
                    'tuesday' => ['open' => '07:00', 'close' => '22:00'],
                    'wednesday' => ['open' => '07:00', 'close' => '22:00'],
                    'thursday' => ['open' => '07:00', 'close' => '22:00'],
                    'friday' => ['open' => '07:00', 'close' => '23:00'],
                    'saturday' => ['open' => '08:00', 'close' => '23:00'],
                    'sunday' => ['open' => '08:00', 'close' => '22:00']
                ],
                'settings' => [
                    'type' => 'flagship',
                    'capacity' => 50,
                    'wifi_ssid' => 'Coffee-Central-Staff',
                    'email' => 'central@coffee.com'
                ]
            ],
            [
                'name' => 'Coffee Express - Sudirman',
                'code' => 'CE-SDM',
                'address' => 'Jl. Sudirman No. 45, Ground Floor, Jakarta, DKI Jakarta 12190',
                'phone' => '+62-21-7890124',
                'latitude' => -6.2033,
                'longitude' => 106.8226,
                'radius' => 30,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'operating_hours' => [
                    'monday' => ['open' => '06:30', 'close' => '20:00'],
                    'tuesday' => ['open' => '06:30', 'close' => '20:00'],
                    'wednesday' => ['open' => '06:30', 'close' => '20:00'],
                    'thursday' => ['open' => '06:30', 'close' => '20:00'],
                    'friday' => ['open' => '06:30', 'close' => '20:00'],
                    'saturday' => ['open' => '08:00', 'close' => '18:00'],
                    'sunday' => ['open' => '08:00', 'close' => '18:00']
                ],
                'settings' => [
                    'type' => 'express',
                    'capacity' => 25,
                    'wifi_ssid' => 'Coffee-Express-Staff',
                    'email' => 'sudirman@coffee.com'
                ]
            ],
            [
                'name' => 'Coffee Corner - Bandung',
                'code' => 'CC-BDG',
                'address' => 'Jl. Asia Afrika No. 123, Bandung, Jawa Barat 40111',
                'phone' => '+62-22-1234567',
                'latitude' => -6.9175,
                'longitude' => 107.6191,
                'radius' => 40,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'operating_hours' => [
                    'monday' => ['open' => '07:00', 'close' => '21:00'],
                    'tuesday' => ['open' => '07:00', 'close' => '21:00'],
                    'wednesday' => ['open' => '07:00', 'close' => '21:00'],
                    'thursday' => ['open' => '07:00', 'close' => '21:00'],
                    'friday' => ['open' => '07:00', 'close' => '22:00'],
                    'saturday' => ['open' => '08:00', 'close' => '22:00'],
                    'sunday' => ['open' => '08:00', 'close' => '21:00']
                ],
                'settings' => [
                    'type' => 'standard',
                    'capacity' => 35,
                    'wifi_ssid' => 'Coffee-Corner-Staff',
                    'email' => 'bandung@coffee.com'
                ]
            ],
            [
                'name' => 'Coffee Spot - Surabaya',
                'code' => 'CS-SBY',
                'address' => 'Jl. Tunjungan Plaza, Level 3, Unit 301, Surabaya, Jawa Timur 60261',
                'phone' => '+62-31-5678910',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'radius' => 45,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'operating_hours' => [
                    'monday' => ['open' => '07:00', 'close' => '21:00'],
                    'tuesday' => ['open' => '07:00', 'close' => '21:00'],
                    'wednesday' => ['open' => '07:00', 'close' => '21:00'],
                    'thursday' => ['open' => '07:00', 'close' => '21:00'],
                    'friday' => ['open' => '07:00', 'close' => '22:00'],
                    'saturday' => ['open' => '08:00', 'close' => '22:00'],
                    'sunday' => ['open' => '08:00', 'close' => '21:00']
                ],
                'settings' => [
                    'type' => 'standard',
                    'capacity' => 40,
                    'wifi_ssid' => 'Coffee-Spot-Staff',
                    'email' => 'surabaya@coffee.com'
                ]
            ],
            [
                'name' => 'Coffee Hub - Yogyakarta',
                'code' => 'CH-YGY',
                'address' => 'Jl. Malioboro No. 88, Yogyakarta, DI Yogyakarta 55213',
                'phone' => '+62-274-123456',
                'latitude' => -7.7956,
                'longitude' => 110.3695,
                'radius' => 35,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'operating_hours' => [
                    'monday' => ['open' => '07:30', 'close' => '21:30'],
                    'tuesday' => ['open' => '07:30', 'close' => '21:30'],
                    'wednesday' => ['open' => '07:30', 'close' => '21:30'],
                    'thursday' => ['open' => '07:30', 'close' => '21:30'],
                    'friday' => ['open' => '07:30', 'close' => '22:30'],
                    'saturday' => ['open' => '08:00', 'close' => '22:30'],
                    'sunday' => ['open' => '08:00', 'close' => '21:30']
                ],
                'settings' => [
                    'type' => 'cozy',
                    'capacity' => 30,
                    'wifi_ssid' => 'Coffee-Hub-Staff',
                    'email' => 'yogya@coffee.com'
                ]
            ]
        ];

        foreach ($branches as $branchData) {
            $branch = Branch::create($branchData);
            $this->command->line("  ✓ Branch: {$branch->name} ({$branch->code})");
        }

        $this->command->info('✅ ' . count($branches) . ' coffee shop branches created!');
        $this->command->info('📍 All branches have GPS coordinates for geofence validation');
    }
}
