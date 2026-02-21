<?php

namespace Database\Seeders;

use App\Models\Filiere;
use Illuminate\Database\Seeder;

class FilieresMasterSeeder extends Seeder
{
    public function run(): void
    {
        $filieres = [
            [
                'nom' => 'Informatique de Gestion',
                'code' => 'MIG',
                'duree_annees' => 2,
                'description' => 'Master oriente SI, data et pilotage des organisations.',
            ],
            [
                'nom' => 'Reseaux et Telecommunications',
                'code' => 'MRT',
                'duree_annees' => 2,
                'description' => 'Master oriente infrastructures reseaux, telecom et securite.',
            ],
            [
                'nom' => 'Finance Comptabilite Audit',
                'code' => 'MFCA',
                'duree_annees' => 2,
                'description' => 'Master oriente finance d entreprise, comptabilite et audit.',
            ],
            [
                'nom' => 'Droit des Affaires',
                'code' => 'MDA',
                'duree_annees' => 2,
                'description' => 'Master oriente droit commercial, fiscal et gouvernance.',
            ],
        ];

        foreach ($filieres as $filiere) {
            Filiere::updateOrCreate(
                ['code' => $filiere['code']],
                [
                    'nom' => $filiere['nom'],
                    'duree_annees' => $filiere['duree_annees'],
                    'description' => $filiere['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Filieres master (M1/M2) seedees.');
    }
}
