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
    public function index(Request $request)
    {
        $anneeId = $request->get('annee_academique_id');

        if (!$anneeId) {
            return response()->json(['message' => 'Le paramètre annee_academique_id est requis'], 422);
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

    public function active()
    {
        $cacheKey = 'semestre:actif';

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () {
            $semestre = Semestre::active()
                ->with('anneeAcademique')
                ->withCount(['inscriptions', 'evaluations'])
                ->first();

            if (!$semestre) {
                return response()->json(['message' => 'Aucun semestre actif', 'data' => null], 404);
            }

            return new SemestreResource($semestre);
        });
    }

    public function show(Semestre $semestre)
    {
        $semestre->load('anneeAcademique')
            ->loadCount(['inscriptions', 'evaluations', 'bulletins', 'emploisDuTemps']);

        return new SemestreResource($semestre);
    }

    public function store(CreateSemestreRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                // Si ce semestre est activé, désactiver les autres de la même année
                if ($request->boolean('is_active')) {
                    Semestre::deactivateAllInAnnee($request->annee_academique_id);
                }

                $semestre = Semestre::create($request->validated());

                LogService::write(
                    ActionLog::CREATE,
                    "Création du semestre {$semestre->numero->value} pour l'année #{$semestre->annee_academique_id}",
                    $semestre,
                    null,
                    $semestre->toArray()
                );

                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$semestre->annee_academique_id}",
                    'annees_academiques:all',          // ← ajouter
                    'annee_academique:active',         // ← ajouter
                    CacheService::KEYS['stats_dashboard'],
                ]);

                return response()->json([
                    'message'  => 'Semestre créé avec succès',
                    'semestre' => new SemestreResource($semestre->load('anneeAcademique')),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateSemestreRequest $request, Semestre $semestre)
    {
        try {
            return DB::transaction(function () use ($request, $semestre) {
                $oldValues = $semestre->toArray();

                if ($request->has('is_active') && $request->boolean('is_active')) {
                    Semestre::deactivateAllInAnnee($semestre->annee_academique_id);
                }

                $semestre->update($request->validated());

                LogService::write(
                    ActionLog::UPDATE,
                    "Mise à jour du semestre {$semestre->numero->value}",
                    $semestre,
                    $oldValues,
                    $semestre->fresh()->toArray()
                );

                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$semestre->annee_academique_id}",
                    'annees_academiques:all',        
                    'annee_academique:active',        
                    CacheService::KEYS['stats_dashboard'],
                ]);

                return response()->json([
                    'message'  => 'Semestre mis à jour avec succès',
                    'semestre' => new SemestreResource($semestre->load('anneeAcademique')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }
    }

    public function activate(Semestre $semestre)
    {
        try {
            return DB::transaction(function () use ($semestre) {
                $oldStatus = $semestre->is_active;

                Semestre::deactivateAllInAnnee($semestre->annee_academique_id);
                $semestre->update(['is_active' => true]);

                LogService::write(
                    ActionLog::UPDATE,
                    "Activation du semestre {$semestre->numero->value}",
                    $semestre,
                    ['is_active' => $oldStatus],
                    ['is_active' => true]
                );

                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$semestre->annee_academique_id}",
                    'annees_academiques:all',          
                    'annee_academique:active',         
                    CacheService::KEYS['stats_dashboard'],
                ]);

                return response()->json([
                    'message'  => 'Semestre activé avec succès',
                    'semestre' => new SemestreResource($semestre->load('anneeAcademique')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'activation', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Semestre $semestre)
    {
        if ($semestre->inscriptions()->exists()) {
            return response()->json([
                'message'            => 'Impossible de supprimer un semestre avec des inscriptions actives',
                'inscriptions_count' => $semestre->inscriptions()->count(),
            ], 422);
        }

        if ($semestre->is_active) {
            return response()->json(['message' => 'Impossible de supprimer le semestre actuellement actif'], 422);
        }

        try {
            return DB::transaction(function () use ($semestre) {
                $anneeId = $semestre->annee_academique_id;
                $numero  = $semestre->numero->value;

                LogService::write(
                    ActionLog::DELETE,
                    "Suppression du semestre {$numero}",
                    $semestre,
                    $semestre->toArray()
                );

                $semestre->delete();

                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$anneeId}",
                    'annees_academiques:all',          
                    'annee_academique:active',         
                    CacheService::KEYS['stats_dashboard'],
                ]);

                return response()->json(['message' => 'Semestre supprimé avec succès']);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }
}