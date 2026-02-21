<?php

namespace Database\Seeders;

use App\Models\Cours;
use App\Models\Etudiant;
use App\Models\Inscription;
use Illuminate\Database\Seeder;

class InscriptionsMasterSeeder extends Seeder
{
    public function run(): void
    {
        $etudiants = Etudiant::query()
            ->whereNotNull('niveau_id')
            ->whereNotNull('annee_academique_id')
            ->get();

        if ($etudiants->isEmpty()) {
            $this->command?->warn('Aucun etudiant trouve. Inscriptions non seedees.');
            return;
        }

        $count = 0;

        foreach ($etudiants as $etudiant) {
            $coursDuNiveau = Cours::query()
                ->where('niveau_id', $etudiant->niveau_id)
                ->where('annee_academique_id', $etudiant->annee_academique_id)
                ->get();

            foreach ($coursDuNiveau as $cours) {
                Inscription::updateOrCreate(
                    [
                        'etudiant_id' => $etudiant->id,
                        'cours_id' => $cours->id,
                        'semestre_id' => $cours->semestre_id,
                    ],
                    [
                        'annee_academique_id' => $cours->annee_academique_id,
                        'date_inscription' => now()->toDateString(),
                    ]
                );

                $count++;
            }
        }

        $this->command?->info("Inscriptions seedees: {$count}");
    }
}
