<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAnneeAcademiqueRequest;
use App\Http\Requests\Admin\UpdateAnneeAcademiqueRequest;
use App\Http\Resources\Admin\AnneeAcademiqueResource;
use App\Http\Resources\Admin\SemestreResource;
use App\Models\AnneeAcademique;
use App\Models\Semestre;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnneeAcademiqueController extends Controller
{
    /**
     * Liste de toutes les années académiques
     */
    public function index()
    {
        $cacheKey = 'annees_academiques:all';

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () {
            $annees = AnneeAcademique::withCount(['semestres', 'etudiants', 'cours'])
                ->orderByDesc('date_debut')
                ->get();

            return AnneeAcademiqueResource::collection($annees);
        });
    }

    /**
     * Année académique active
     */
    public function active()
    {
        $cacheKey = 'annee_academique:active';

        return Cache::remember($cacheKey, CacheService::LONG_TTL, function () {
            $annee = AnneeAcademique::active()
                ->with('semestres')
                ->withCount(['etudiants', 'cours'])
                ->first();

            if (!$annee) {
                return response()->json([
                    'message' => 'Aucune année académique active',
                    'data' => null,
                ], 404);
            }

            return new AnneeAcademiqueResource($annee);
        });
    }

    /**
     * Détails d'une année académique
     */
    public function show(AnneeAcademique $anneeAcademique)
    {
        $anneeAcademique->load('semestres')
            ->loadCount(['etudiants', 'cours', 'inscriptions']);

        return new AnneeAcademiqueResource($anneeAcademique);
    }

    /**
     * Créer une année académique
     */
    public function store(CreateAnneeAcademiqueRequest $request)
    {
        try {
            DB::beginTransaction();

            if ($request->is_active) {
                AnneeAcademique::deactivateAll(); 
            }

            $annee = AnneeAcademique::create($request->validated());

            LogService::write(
                ActionLog::CREATE,
                "Création de l'année académique : {$annee->nom}",
                $annee,
                null,
                $annee->toArray()
            );

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
                CacheService::KEYS['stats_dashboard']
            ]);

            return response()->json([
                'message' => 'Année académique créée avec succès',
                'annee' => new AnneeAcademiqueResource($annee),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une année académique
     */
    public function update(UpdateAnneeAcademiqueRequest $request, AnneeAcademique $anneeAcademique)
    {
        try {
            DB::beginTransaction();

            $oldValues = $anneeAcademique->toArray();

            if ($request->has('is_active') && $request->is_active) {
                AnneeAcademique::deactivateAll();
            }

            $anneeAcademique->update($request->validated());

            LogService::write(
                ActionLog::UPDATE,
                "Mise à jour de l'année académique : {$anneeAcademique->nom}",
                $anneeAcademique,
                $oldValues,
                $anneeAcademique->fresh()->toArray()
            );

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
            ]);

            return response()->json([
                'message' => 'Année académique mise à jour avec succès',
                'annee' => new AnneeAcademiqueResource($anneeAcademique), 
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activer une année académique
     */
    public function activate(AnneeAcademique $anneeAcademique)
    {
        try {
            DB::beginTransaction();

            if ($anneeAcademique->is_cloturee) {
                return response()->json([
                    'message' => 'Impossible d\'activer une année clôturée',
                ], 422);
            }

            AnneeAcademique::deactivateAll();
            $anneeAcademique->update(['is_active' => true]);

            // --- LOG SERVICE ---
            LogService::write(
                ActionLog::VALIDATE,
                "Activation manuelle de l'année académique : {$anneeAcademique->nom}",
                $anneeAcademique
            );

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
            ]);

            return response()->json([
                'message' => 'Année académique activée avec succès',
                'annee' => new AnneeAcademiqueResource($anneeAcademique),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'activation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clôturer une année académique
     */
    public function close(AnneeAcademique $anneeAcademique)
    {
        try {
            DB::beginTransaction();

            $anneeAcademique->update([
                'is_cloturee' => true,
                'is_active' => false,
            ]);

            // --- LOG SERVICE ---
            LogService::write(
                ActionLog::VALIDATE,
                "Clôture officielle de l'année académique : {$anneeAcademique->nom}",
                $anneeAcademique
            );

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
            ]);

            return response()->json([
                'message' => 'Année académique clôturée avec succès',
                'annee' => new AnneeAcademiqueResource($anneeAcademique),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la clôture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer automatiquement les deux semestres
     */
    public function createSemestres(AnneeAcademique $anneeAcademique)
    {
        try {
            DB::beginTransaction();

            if ($anneeAcademique->semestres()->count() >= 2) {
                return response()->json([
                    'message' => 'Les semestres existent déjà pour cette année',
                ], 422);
            }

            $dateDebut = $anneeAcademique->date_debut;
            $dateFin = $anneeAcademique->date_fin;

            $anneeSuivante = $dateDebut->year + 1;
            $s1Fin = Carbon::create($anneeSuivante, 1, 31); 

            if ($s1Fin->gt($dateFin)) {
                $s1Fin = $dateFin->copy()->subMonths(4);
            }

            $s2Debut = $s1Fin->copy()->addDay();

            // Semestre 1
            $s1 = Semestre::create([
                'annee_academique_id' => $anneeAcademique->id,
                'numero' => 'S1',
                'date_debut' => $dateDebut,
                'date_fin' => $s1Fin,
                'date_debut_examens' => $s1Fin->copy()->subWeeks(2),
                'date_fin_examens' => $s1Fin,
                'is_active' => true,
            ]);

            // Semestre 2
            $s2 = Semestre::create([
                'annee_academique_id' => $anneeAcademique->id,
                'numero' => 'S2',
                'date_debut' => $s2Debut,
                'date_fin' => $dateFin,
                'date_debut_examens' => $dateFin->copy()->subWeeks(2),
                'date_fin_examens' => $dateFin,
                'is_active' => false,
            ]);

            // --- LOG SERVICE ---
            LogService::write(
                ActionLog::CREATE,
                "Génération automatique des semestres S1 et S2 pour l'année {$anneeAcademique->nom}",
                $anneeAcademique
            );

            DB::commit();

            CacheService::forget([
                'semestres:*',
                'semestre:actif',
                'annees_academiques:all'
            ]);

            return response()->json([
                'message' => 'Semestres créés automatiquement',
                'semestres' => SemestreResource::collection($anneeAcademique->fresh()->semestres),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création des semestres',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une année académique
     */
    public function destroy(AnneeAcademique $anneeAcademique)
    {
        if ($anneeAcademique->etudiants()->exists()) { 
            return response()->json([
                'message' => 'Impossible de supprimer une année avec des étudiants inscrits',
            ], 422);
        }

        if ($anneeAcademique->is_active) {
            return response()->json([
                'message' => 'Impossible de supprimer l\'année académique active',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $nomAnnee = $anneeAcademique->nom;

            // --- LOG SERVICE --- (Avant suppression pour garder l'ID en mémoire)
            LogService::write(
                ActionLog::DELETE,
                "Suppression définitive de l'année académique : {$nomAnnee}",
                $anneeAcademique
            );

            $anneeAcademique->delete();

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
                CacheService::KEYS['stats_dashboard']
            ]);

            return response()->json([
                'message' => 'Année académique supprimée avec succès',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }
}