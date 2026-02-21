<?php

namespace App\Services;

use App\Models\EmploiDuTemps;
use App\Models\Professeur;
use App\Models\Semestre;
use App\Models\AnneeAcademique;
use App\Enums\JourSemaine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class EmploiDuTempsService
{
    /**
     * Vérifier les conflits (niveau, professeur, salle)
     */
    public function checkAllConflicts(array $data): ?array
    {
        // Conflit NIVEAU (Le groupe d'élèves ne peut pas avoir deux cours en même temps)
        if ($this->hasConflict($data, 'niveau_id', $data['niveau_id'])) {
            return [
                'type' => 'niveau',
                'message' => 'Ce niveau a déjà cours sur ce créneau.',
                'details' => [
                    'niveau_id' => $data['niveau_id'],
                    'jour' => $data['jour'],
                    'creneau' => "{$data['heure_debut']} - {$data['heure_fin']}"
                ]
            ];
        }

        // Conflit PROFESSEUR (Un prof ne peut pas être à deux endroits à la fois)
        if ($this->hasConflict($data, 'professeur_id', $data['professeur_id'])) {
            return [
                'type' => 'professeur',
                'message' => 'Ce professeur est déjà occupé sur ce créneau.',
                'details' => [
                    'professeur_id' => $data['professeur_id'],
                    'jour' => $data['jour'],
                    'creneau' => "{$data['heure_debut']} - {$data['heure_fin']}"
                ]
            ];
        }

        // Conflit SALLE (Une salle ne peut pas accueillir deux cours simultanément)
        if (isset($data['salle_id']) && $this->hasConflict($data, 'salle_id', $data['salle_id'])) {
            return [
                'type' => 'salle',
                'message' => 'Cette salle est déjà occupée sur ce créneau.',
                'details' => [
                    'salle_id' => $data['salle_id'],
                    'jour' => $data['jour'],
                    'creneau' => "{$data['heure_debut']} - {$data['heure_fin']}"
                ]
            ];
        }

        return null;
    }

    /**
     * Détection de chevauchement générique.
     */
    private function hasConflict(array $data, string $column, int $id): bool
    {
        return EmploiDuTemps::where($column, $id)
            ->where('semestre_id', $data['semestre_id'])
            ->where('jour', $data['jour'])
            ->where(function ($query) use ($data) {
                // Formule mathématique de chevauchement de créneaux :
                // (Debut1 < Fin2) ET (Fin1 > Debut2)
                $query->where('heure_debut', '<', $data['heure_fin'])
                      ->where('heure_fin', '>', $data['heure_debut']);
            })
            ->exists();
    }

    /**
     * Invalider tous les caches après création/suppression
     */
    public function invalidateCacheAfterUpdate(array $data): void
    {
        CacheService::forgetProfPlanning($data['professeur_id']);
        CacheService::forgetNiveauPlanning($data['niveau_id'], $data['semestre_id']);

        if (isset($data['salle_id'])) {
            Cache::forget(sprintf('planning_salle_%d_sem_%d', $data['salle_id'], $data['semestre_id']));
        }

        $this->invalidateHelperCaches($data);
        CacheService::forget(CacheService::KEYS['stats_dashboard'] ?? 'stats_dashboard');
    }

    private function invalidateHelperCaches(array $data): void
    {
        // On utilise des tags ou des clés spécifiques si votre driver le permet
        Cache::forget(sprintf('profs_disponibles_niv_%d_sem_%d_*', $data['niveau_id'], $data['semestre_id']));
        Cache::forget(sprintf('cours_disponibles_prof_%d_niv_%d_sem_%d', $data['professeur_id'], $data['niveau_id'], $data['semestre_id']));
    }

    /**
     * Trouver les professeurs qui enseignent dans ce niveau et qui sont libres.
     */
    public function getProfesseursDisponibles(array $validated): Collection
    {
        $anneeAcademiqueId = $validated['annee_academique_id'] 
            ?? AnneeAcademique::active()?->id;

        // Récupérer les IDs des profs rattachés à ce niveau pour l'année en cours
        $professeurIdsNiveau = DB::table('cours_professeur')
            ->join('cours', 'cours_professeur.cours_id', '=', 'cours.id')
            ->where('cours.niveau_id', $validated['niveau_id'])
            ->where('cours_professeur.annee_academique_id', $anneeAcademiqueId)
            ->pluck('cours_professeur.professeur_id')
            ->unique();

        // Filtrer ceux qui n'ont pas de conflit d'emploi du temps
        return Professeur::whereIn('id', $professeurIdsNiveau)
            ->whereDoesntHave('emploisDuTemps', function ($q) use ($validated) {
                $q->where('semestre_id', $validated['semestre_id'])
                    ->where('jour', $validated['jour'])
                    ->where('heure_debut', '<', $validated['heure_fin'])
                    ->where('heure_fin', '>', $validated['heure_debut']);
            })
            ->with('user:id,name,email')
            ->get()
            ->map(fn($prof) => [
                'id' => $prof->id,
                'nom_complet' => $prof->nom_complet,
                'specialite' => $prof->specialite,
                'email' => $prof->email_professionnel,
            ]);
    }

    /**
     * Trouver les cours disponibles pour un binôme Professeur/Niveau donné
     */
    public function getCoursDisponibles(array $validated): Collection
    {
        // On récupère l'objet, pas juste la requête
        $anneeActive = AnneeAcademique::where('is_active', true)->first();
        $anneeId = $anneeActive ? $anneeActive->id : null;

        return DB::table('cours_professeur')
            ->join('cours', 'cours_professeur.cours_id', '=', 'cours.id')
            ->where('cours_professeur.professeur_id', $validated['professeur_id'])
            ->where('cours.niveau_id', $validated['niveau_id'])
            ->where('cours.semestre_id', $validated['semestre_id'])
            ->when($anneeId, function($q) use ($anneeId) {
                return $q->where('cours_professeur.annee_academique_id', $anneeId);
            })
            ->select('cours.id', 'cours.titre', 'cours.code')
            ->get();
}
}