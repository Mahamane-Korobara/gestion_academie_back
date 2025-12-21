<?php

namespace App\Services;

use App\Models\EmploiDuTemps;
use App\Models\Semestre;
use App\Enums\JourSemaine;

class EmploiDuTempsEtudiantService
{
    /**
     * Récupérer le semestre actif ou spécifié
     */
    public function getSemestre(?int $semestreId = null): ?Semestre
    {
        if ($semestreId) {
            return Semestre::find($semestreId);
        }
        return Semestre::where('is_active', true)->first();
    }

    /**
     * Générer l'emploi du temps complet
     */
    public function getEmploiDuTemps(int $niveauId, int $semestreId, ?string $jour = null)
    {
        return EmploiDuTemps::where('niveau_id', $niveauId)
            ->where('semestre_id', $semestreId)
            ->when($jour, fn($q) => $q->where('jour', $jour))
            ->with([
                'cours:id,titre,code,coefficient',
                'professeur:id,nom,prenom,specialite,grade,email_professionnel',
                'salle:id,nom,batiment,capacite'
            ])
            ->get()
            ->sortBy(fn($e) => $e->jour->numero())
            ->values();
    }

    /**
     * Organiser par jour (format calendrier)
     */
    public function organiserParJour($emplois): array
    {
        $planning = [];
        foreach (JourSemaine::cases() as $jour) {
            $planning[$jour->value] = $emplois
                ->where('jour', $jour)
                ->sortBy('heure_debut')
                ->values()
                ->map(fn($emploi) => [
                    'id' => $emploi->id,
                    'cours' => $emploi->cours->titre,
                    'code_cours' => $emploi->cours->code,
                    'professeur' => $emploi->professeur->nom_complet,
                    'salle' => $emploi->salle?->nom ?? 'À définir',
                    'type' => $emploi->type_seance->label(),
                    'type_color' => $emploi->type_seance->color(),
                    'heure_debut' => $emploi->heure_debut->format('H:i'),
                    'heure_fin' => $emploi->heure_fin->format('H:i'),
                    'duree_minutes' => $emploi->heure_debut->diffInMinutes($emploi->heure_fin),
                ]);
        }
        return $planning;
    }

    /**
     * Calculer les statistiques
     */
    public function calculerStatistiques(array $planning): array
    {
        $totalSeances = 0;
        $totalMinutes = 0;

        foreach ($planning as $seances) {
            $totalSeances += count($seances);
            $totalMinutes += array_sum(array_column($seances->toArray(), 'duree_minutes'));
        }

        return [
            'total_seances_semaine' => $totalSeances,
            'total_heures_semaine' => round($totalMinutes / 60, 2),
            'moyenne_heures_jour' => $totalSeances > 0 ? round(($totalMinutes / 60) / 5, 2) : 0,
        ];
    }

    /**
     * Récupérer les prochains cours
     */
    public function getProchainsCours(int $niveauId, int $semestreId, string $jourActuel, string $heureActuelle)
    {
        // Cours d'aujourd'hui
        $coursAujourdhui = EmploiDuTemps::where('niveau_id', $niveauId)
            ->where('semestre_id', $semestreId)
            ->where('jour', ucfirst($jourActuel))
            ->where('heure_debut', '>=', $heureActuelle)
            ->with(['cours', 'professeur', 'salle'])
            ->orderBy('heure_debut')
            ->limit(3)
            ->get();

        // Cours de la semaine
        $joursSuivants = [];
        for ($i = 1; $i <= 5; $i++) {
            $joursSuivants[] = ucfirst(now()->addDays($i)->locale('fr')->dayName);
        }

        $coursSemaine = EmploiDuTemps::where('niveau_id', $niveauId)
            ->where('semestre_id', $semestreId)
            ->whereIn('jour', $joursSuivants)
            ->with(['cours', 'professeur'])
            ->get()
            ->sortBy(fn($e) => $e->jour->numero())
            ->values()
            ->take(5);

        return [
            'aujourdhui' => $coursAujourdhui,
            'semaine' => $coursSemaine,
        ];
    }
}