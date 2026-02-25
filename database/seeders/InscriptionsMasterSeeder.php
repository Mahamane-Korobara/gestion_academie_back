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

        $this->command?->info("Total etudiants a inscrire: {$etudiants->count()}");

        $count = 0;
        $etudiantsNonInscrits = [];

        foreach ($etudiants as $etudiant) {
            $coursDuNiveau = Cours::query()
                ->where('niveau_id', $etudiant->niveau_id)
                ->where('annee_academique_id', $etudiant->annee_academique_id)
                ->where('is_active', true)
                ->get();

            if ($coursDuNiveau->isEmpty()) {
                $etudiantsNonInscrits[] = "{$etudiant->prenom} {$etudiant->nom} (niveau_id: {$etudiant->niveau_id})";
                continue;
            }

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

        if (!empty($etudiantsNonInscrits)) {
            $this->command?->warn("Etudiants NON inscrits (pas de cours pour leur niveau): " . count($etudiantsNonInscrits));
            foreach (array_slice($etudiantsNonInscrits, 0, 5) as $msg) {
                $this->command?->warn("  - $msg");
            }
        }

        $this->command?->info("Inscriptions seedees: {$count} inscription(s) creees.");
    }
}
