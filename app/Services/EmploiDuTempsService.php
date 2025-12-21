<?php

namespace App\Services;

use App\Models\EmploiDuTemps;
use App\Models\Professeur;
use App\Models\Semestre;
use App\Models\AnneeAcademique;
use App\Enums\JourSemaine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EmploiDuTempsService
{
    /**
     * Vérifier les conflits (niveau, professeur, salle)
     */
    public function checkAllConflicts(array $data): ?array
    {
        // Conflit NIVEAU
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

        // Conflit PROFESSEUR
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

        // Conflit SALLE
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
     * Détection de chevauchement.
     */
    private function hasConflict(array $data, string $column, int $id): bool
    {
        return EmploiDuTemps::where($column, $id)
            ->where('semestre_id', $data['semestre_id'])
            ->where('jour', $data['jour'])
            ->where(function ($query) use ($data) {
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
        // Invalider le cache du professeur
        CacheService::forgetProfPlanning($data['professeur_id']);
        
        // Invalider le cache du niveau
        CacheService::forgetNiveauPlanning($data['niveau_id'], $data['semestre_id']);

        // Invalider le cache de la salle
        if (isset($data['salle_id'])) {
            Cache::forget(sprintf('planning_salle_%d_sem_%d', $data['salle_id'], $data['semestre_id']));
        }

        // Invalider les caches des helpers
        $this->invalidateHelperCaches($data);
        CacheService::forget(CacheService::KEYS['stats_dashboard']);
    }

    private function invalidateHelperCaches(array $data): void
    {
        Cache::forget(sprintf('profs_disponibles_niv_%d_sem_%d_*', $data['niveau_id'], $data['semestre_id']));
        Cache::forget(sprintf('cours_disponibles_prof_%d_niv_%d_sem_%d', $data['professeur_id'], $data['niveau_id'], $data['semestre_id']));
    }

    /**
     * Trouver les professeurs disponibles
     */
    public function getProfesseursDisponibles(array $validated): object
    {
        $anneeAcademiqueId = $validated['annee_academique_id'] 
            ?? AnneeAcademique::active()?->id;

        $professeurIdsNiveau = DB::table('cours_professeur')
            ->join('cours', 'cours_professeur.cours_id', '=', 'cours.id')
            ->where('cours.niveau_id', $validated['niveau_id'])
            ->where('cours_professeur.annee_academique_id', $anneeAcademiqueId)
            ->pluck('cours_professeur.professeur_id')
            ->unique();

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
     * Trouver les cours disponibles
     */
    public function getCoursDisponibles(array $validated): object
    {
        $semestre = Semestre::findOrFail($validated['semestre_id']);

        return DB::table('cours_professeur')
            ->join('cours', 'cours_professeur.cours_id', '=', 'cours.id')
            ->where('cours_professeur.professeur_id', $validated['professeur_id'])
            ->where('cours.niveau_id', $validated['niveau_id'])
            ->where('cours.semestre', $semestre->numero->value)
            ->select('cours.id', 'cours.titre', 'cours.code')
            ->get();
    }
}