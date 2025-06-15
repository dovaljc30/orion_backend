<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FermentationSeeder extends Seeder
{
    public function run(): void
    {
        // Primero creamos las fermentaciones
        $fermentations = [
            [
                'device_id' => 1,
                'start_time' => '2024-03-21 10:00:00',
                'type' => 'Premium',
                'note' => 'Fermentación Premium con un solo genotipo',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'device_id' => 2,
                'start_time' => '2024-03-21 10:00:00',
                'type' => 'Especial',
                'note' => 'Fermentación Especial con múltiples genotipos',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'device_id' => 3,
                'start_time' => '2024-03-21 10:00:00',
                'type' => 'Premium',
                'end_time' => '2024-03-21 15:00:00',
                'note' => 'Fermentación Premium con genotipo único',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'device_id' => 4,
                'start_time' => '2024-03-21 10:00:00',
                'type' => 'Especial',
                'note' => 'Fermentación Especial con mezcla de genotipos',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // Insertamos las fermentaciones y guardamos sus IDs
        foreach ($fermentations as $fermentation) {
            $id = DB::table('fermentations')->insertGetId($fermentation);
            
            // Asignamos los genotipos según el tipo de fermentación
            if ($fermentation['type'] === 'Premium') {
                // Para Premium, solo un genotipo
                DB::table('fermentation_genotype')->insert([
                    'fermentation_id' => $id,
                    'genotype_id' => 1, // Genotipo A
                    'quantity' => 500.00,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                // Para Especial, múltiples genotipos
                DB::table('fermentation_genotype')->insert([
                    [
                        'fermentation_id' => $id,
                        'genotype_id' => 2, // Genotipo B
                        'quantity' => 300.00,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ],
                    [
                        'fermentation_id' => $id,
                        'genotype_id' => 3, // Genotipo C
                        'quantity' => 200.00,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ],
                    [
                        'fermentation_id' => $id,
                        'genotype_id' => 4, // Genotipo D
                        'quantity' => 100.00,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ],
                ]);
            }
        }
    }
} 