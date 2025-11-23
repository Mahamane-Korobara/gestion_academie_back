<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'nom' => 'Contrôle Continu',
                'code' => 'CC',
                'coefficient_defaut' => 0.40,
                'description' => 'Évaluation continue durant le semestre',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Examen Final',
                'code' => 'EF',
                'coefficient_defaut' => 0.60,
                'description' => 'Examen de fin de semestre',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Travaux Pratiques',
                'code' => 'TP',
                'coefficient_defaut' => 0.30,
                'description' => 'Évaluation des travaux pratiques',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Projet',
                'code' => 'PROJ',
                'coefficient_defaut' => 0.40,
                'description' => 'Projet de fin de module',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Rattrapage',
                'code' => 'RATT',
                'coefficient_defaut' => 1.00,
                'description' => 'Session de rattrapage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('types_evaluations')->insert($types);

        $this->command->info('5 types d\'évaluation créés avec succès');
    }
}
