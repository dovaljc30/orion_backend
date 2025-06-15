<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenotypeSeeder extends Seeder
{
    public function run(): void
    {
        $genotypes = [
            [
                'name' => 'Genotipo A',
                'code' => 'GTA001',
                'description' => 'Genotipo de alta calidad para fermentación Premium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Genotipo B',
                'code' => 'GTA002',
                'description' => 'Genotipo especial para mezclas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Genotipo C',
                'code' => 'GTA003',
                'description' => 'Genotipo versátil para diferentes tipos de fermentación',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Genotipo D',
                'code' => 'GTA004',
                'description' => 'Genotipo premium para fermentaciones especiales',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('genotypes')->insert($genotypes);
    }
} 