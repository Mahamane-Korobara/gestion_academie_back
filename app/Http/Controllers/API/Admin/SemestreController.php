<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateSemestreRequest;
use App\Http\Requests\Admin\UpdateSemestreRequest;
use App\Http\Resources\Admin\SemestreResource;
use App\Models\Semestre;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SemestreController extends Controller
{
    /**
     * Liste des semestres d'une année académique
     */
    public function index(Request $request)
    {
        $anneeId = $request->get('annee_academique_id');

        if (!$anneeId) {
            return response()->json([
                'message' => 'Le paramètre annee_academique_id est requis',
            ], 422);
        }

        $cacheKey = "semestres:annee:{$anneeId}";

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($anneeId) {
            $semestres = Semestre::where('annee_academique_id', $anneeId)
                ->with('anneeAcademique')
                ->withCount(['inscriptions', 'evaluations', 'bulletins'])
                ->orderBy('numero')
                ->get();

            return SemestreResource::collection($semestres);
        });
    }

    /**
     * Semestre actif
     */
    public function active()
    {
        $cacheKey = 'semestre:actif';

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () {
            $semestre = Semestre::active()
                ->with('anneeAcademique')
                ->withCount(['inscriptions', 'evaluations'])
                ->first();

            if (!$semestre) {
                return response()->json([
                    'message' => 'Aucun semestre actif',
                    'data' => null,
                ], 404);
            }

            return new SemestreResource($semestre);
        });
    }

    /**
     * Détails d'un semestre
     */
    public function show(Semestre $semestre)
    {
        $semestre->load('anneeAcademique')
            ->loadCount(['inscriptions', 'evaluations', 'bulletins', 'emploisDuTemps']);

        return new SemestreResource($semestre);
    }

    /**
     * Créer un semestre
     */
    public function store(CreateSemestreRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $semestre = Semestre::create($request->validated());

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::CREATE,
                    "Création du semestre : {$semestre->libelle} (Numéro: {$semestre->numero})",
                    $semestre,
                    null,
                    $semestre->toArray()
                );

                // Invalider les caches
                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$request->annee_academique_id}",
                    CacheService::KEYS['stats_dashboard']
                ]);

                return response()->json([
                    'message' => 'Semestre créé avec succès',
                    'semestre' => new SemestreResource($semestre->load('anneeAcademique')),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour un semestre
     */
    public function update(UpdateSemestreRequest $request, Semestre $semestre)
    {
        try {
            return DB::transaction(function () use ($request, $semestre) {
                $oldValues = $semestre->toArray();

                if ($request->has('is_active') && $request->is_active) {
                    Semestre::deactivateAllInAnnee($semestre->annee_academique_id);
                }

                $semestre->update($request->validated());

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Mise à jour du semestre : {$semestre->libelle}",
                    $semestre,
                    $oldValues,
                    $semestre->fresh()->toArray()
                );

                // Invalider les caches
                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$semestre->annee_academique_id}",
                ]);

                return response()->json([
                    'message' => 'Semestre mis à jour avec succès',
                    'semestre' => new SemestreResource($semestre->load('anneeAcademique')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Activer un semestre
     */
    public function activate(Semestre $semestre)
    {
        try {
            return DB::transaction(function () use ($semestre) {
                $oldStatus = $semestre->is_active;
                
                Semestre::deactivateAllInAnnee($semestre->annee_academique_id);
                $semestre->update(['is_active' => true]);

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Activation manuelle du semestre : {$semestre->libelle}",
                    $semestre,
                    ['is_active' => $oldStatus],
                    ['is_active' => true]
                );

                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$semestre->annee_academique_id}",
                    CacheService::KEYS['stats_dashboard']
                ]);

                return response()->json([
                    'message' => 'Semestre activé avec succès',
                    'semestre' => new SemestreResource($semestre->load('anneeAcademique')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'activation', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un semestre
     */
    public function destroy(Semestre $semestre)
    {
        if ($semestre->inscriptions()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un semestre avec des inscriptions actives',
                'inscriptions_count' => $semestre->inscriptions()->count(),
            ], 422);
        }

        if ($semestre->is_active) {
            return response()->json([
                'message' => 'Impossible de supprimer le semestre actuellement actif',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($semestre) {
                $anneeId = $semestre->annee_academique_id;
                $libelle = $semestre->libelle;
                $oldData = $semestre->toArray();

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::DELETE,
                    "Suppression du semestre : {$libelle}",
                    $semestre,
                    $oldData
                );

                $semestre->delete();

                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$anneeId}",
                    CacheService::KEYS['stats_dashboard']
                ]);

                return response()->json([
                    'message' => 'Semestre supprimé avec succès',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }
}