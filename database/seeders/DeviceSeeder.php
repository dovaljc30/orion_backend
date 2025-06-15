<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = [
            [
                'serial_number' => 'DEV001',
                'code' => 'FERM-001'
            ],
            [
                'serial_number' => 'DEV002',
                'code' => 'FERM-002'
            ],
            [
                'serial_number' => 'DEV003',
                'code' => 'FERM-003'
            ],
            [
                'serial_number' => 'DEV004',
                'code' => 'FERM-004'
            ]
        ];

        foreach ($devices as $device) {
            Device::create($device);
        }
    }
}
