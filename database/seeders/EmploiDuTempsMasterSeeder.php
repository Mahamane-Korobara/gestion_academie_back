<?php

namespace Database\Seeders;

use App\Models\Cours;
use App\Models\EmploiDuTemps;
use App\Models\Professeur;
use App\Models\Salle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploiDuTempsMasterSeeder extends Seeder
{
    public function run(): void
    {
        $coursList = Cours::with(['niveau', 'semestre'])
            ->whereNotNull('annee_academique_id')
            ->whereNotNull('niveau_id')
            ->whereNotNull('semestre_id')
            ->orderBy('annee_academique_id')
            ->orderBy('niveau_id')
            ->orderBy('semestre_id')
            ->orderBy('id')
            ->get();

        if ($coursList->isEmpty()) {
            $this->command?->warn('Aucun cours planifiable trouve. Emploi du temps non seedee.');
            return;
        }

        $professeurs = Professeur::orderBy('id')->get();
        if ($professeurs->isEmpty()) {
            $this->command?->warn('Aucun professeur trouve. Emploi du temps non seedee.');
            return;
        }

        $salles = $this->ensureSalles();

        $slots = $this->buildSlots();

        $usedProfSlots = [];
        $usedNiveauSlots = [];
        $usedSalleSlots = [];
        $profIndex = 0;

        DB::transaction(function () use (
            $coursList,
            $professeurs,
            $salles,
            $slots,
            &$usedProfSlots,
            &$usedNiveauSlots,
            &$usedSalleSlots,
            &$profIndex
        ): void {
            foreach ($coursList as $courseIndex => $cours) {
                $anneeId = (int) $cours->annee_academique_id;
                $semestreId = (int) $cours->semestre_id;
                $niveauId = (int) $cours->niveau_id;
                $salle = $salles[$courseIndex % count($salles)];
                $prof = $this->resolveProfesseurForCours($cours->id, $anneeId, $professeurs, $profIndex);

                // Synchronisation requise par StoreEmploiDuTempsRequest:
                // un professeur doit etre affecte au cours pour la meme annee academique.
                DB::table('cours_professeur')->updateOrInsert(
                    [
                        'cours_id' => $cours->id,
                        'professeur_id' => $prof->id,
                        'annee_academique_id' => $anneeId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $slot = $this->pickSlotWithoutConflict(
                    $slots,
                    $usedProfSlots,
                    $usedNiveauSlots,
                    $usedSalleSlots,
                    $anneeId,
                    $semestreId,
                    $niveauId,
                    $prof->id,
                    $salle->id,
                    $courseIndex
                );

                EmploiDuTemps::updateOrCreate(
                    [
                        'cours_id' => $cours->id,
                        'annee_academique_id' => $anneeId,
                    ],
                    [
                        'niveau_id' => $niveauId,
                        'professeur_id' => $prof->id,
                        'salle_id' => $salle->id,
                        'semestre_id' => $semestreId,
                        'jour' => $slot['jour'],
                        'heure_debut' => $slot['debut'],
                        'heure_fin' => $slot['fin'],
                        'type_seance' => str_contains(strtolower($cours->titre), 'memoire') ? 'td' : 'cours',
                    ]
                );
            }
        });

        $this->command?->info('Emploi du temps seedee pour tous les cours et synchronise avec cours_professeur.');
    }

    /**
     * @return array<int, Salle>
     */
    private function ensureSalles(): array
    {
        $rooms = [
            ['nom' => 'Salle M-101', 'batiment' => 'Bloc Master A', 'capacite' => 45],
            ['nom' => 'Salle M-102', 'batiment' => 'Bloc Master A', 'capacite' => 45],
            ['nom' => 'Salle M-201', 'batiment' => 'Bloc Master B', 'capacite' => 35],
            ['nom' => 'Salle M-202', 'batiment' => 'Bloc Master B', 'capacite' => 35],
            ['nom' => 'Salle Info-1', 'batiment' => 'Laboratoire Numerique', 'capacite' => 30],
            ['nom' => 'Salle Info-2', 'batiment' => 'Laboratoire Numerique', 'capacite' => 30],
        ];

        $salles = [];

        foreach ($rooms as $room) {
            $salles[] = Salle::updateOrCreate(
                ['nom' => $room['nom']],
                [
                    'batiment' => $room['batiment'],
                    'capacite' => $room['capacite'],
                    'equipements' => 'Projecteur, Sonorisation',
                    'is_disponible' => true,
                ]
            );
        }

        return $salles;
    }

    /**
     * @return array<int, array{jour:string,debut:string,fin:string}>
     */
    private function buildSlots(): array
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
        $plages = [
            ['debut' => '08:00:00', 'fin' => '10:00:00'],
            ['debut' => '10:15:00', 'fin' => '12:15:00'],
            ['debut' => '13:00:00', 'fin' => '15:00:00'],
            ['debut' => '15:15:00', 'fin' => '17:15:00'],
            ['debut' => '17:30:00', 'fin' => '19:30:00'],
        ];

        $slots = [];

        foreach ($jours as $jour) {
            foreach ($plages as $plage) {
                $slots[] = [
                    'jour' => $jour,
                    'debut' => $plage['debut'],
                    'fin' => $plage['fin'],
                ];
            }
        }

        return $slots;
    }

    private function resolveProfesseurForCours(int $coursId, int $anneeId, $professeurs, int &$profIndex): Professeur
    {
        $profId = DB::table('cours_professeur')
            ->where('cours_id', $coursId)
            ->where('annee_academique_id', $anneeId)
            ->value('professeur_id');

        if ($profId) {
            $existing = $professeurs->firstWhere('id', $profId);
            if ($existing) {
                return $existing;
            }
        }

        $prof = $professeurs[$profIndex % $professeurs->count()];
        $profIndex++;

        return $prof;
    }

    /**
     * @param array<int, array{jour:string,debut:string,fin:string}> $slots
     * @param array<string, bool> $usedProfSlots
     * @param array<string, bool> $usedNiveauSlots
     * @param array<string, bool> $usedSalleSlots
     * @return array{jour:string,debut:string,fin:string}
     */
    private function pickSlotWithoutConflict(
        array $slots,
        array &$usedProfSlots,
        array &$usedNiveauSlots,
        array &$usedSalleSlots,
        int $anneeId,
        int $semestreId,
        int $niveauId,
        int $profId,
        int $salleId,
        int $offset
    ): array {
        $count = count($slots);

        for ($i = 0; $i < $count; $i++) {
            $slot = $slots[($offset + $i) % $count];

            $profKey = implode('|', [$anneeId, $profId, $slot['jour'], $slot['debut'], $slot['fin']]);
            $niveauKey = implode('|', [$anneeId, $semestreId, $niveauId, $slot['jour'], $slot['debut'], $slot['fin']]);
            $salleKey = implode('|', [$anneeId, $salleId, $slot['jour'], $slot['debut'], $slot['fin']]);

            if (isset($usedProfSlots[$profKey]) || isset($usedNiveauSlots[$niveauKey]) || isset($usedSalleSlots[$salleKey])) {
                continue;
            }

            $usedProfSlots[$profKey] = true;
            $usedNiveauSlots[$niveauKey] = true;
            $usedSalleSlots[$salleKey] = true;

            return $slot;
        }

        throw new \RuntimeException(
            "Aucun creneau disponible pour annee={$anneeId}, semestre={$semestreId}, niveau={$niveauId}."
        );
    }
}
