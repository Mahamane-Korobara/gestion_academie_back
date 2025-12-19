<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateEvaluationRequest;
use App\Http\Requests\Admin\UpdateEvaluationRequest;
use App\Http\Resources\Admin\EvaluationResource;
use App\Models\Evaluation;
use App\Models\Cours;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    /**
     * Liste des évaluations d'un cours (avec cache)
     */
    public function index(Request $request, Cours $cours)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $cacheKey = "evaluations:cours:{$cours->id}:page:{$page}:per_page:$perPage";

        $evaluations = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($cours, $perPage) {
            return $cours->evaluations()
                ->with(['typeEvaluation', 'semestre', 'salle'])
                ->latest('date_evaluation')
                ->paginate($perPage);
        });

        return EvaluationResource::collection($evaluations);
    }

    /**
     * Détails d'une évaluation
     */
    public function show(Evaluation $evaluation)
    {
        $cacheKey = "evaluation:{$evaluation->id}";
        
        $data = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($evaluation) {
            return $evaluation->load(['cours', 'typeEvaluation', 'semestre', 'salle']);
        });

        return new EvaluationResource($data);
    }

    /**
     * Créer une évaluation
     */
    public function store(CreateEvaluationRequest $request, Cours $cours)
    {
        try {
            $evaluation = DB::transaction(function () use ($request, $cours) {
                $evaluationData = $request->validated();
                $evaluationData['cours_id'] = $cours->id;
                
                $newEvaluation = Evaluation::create($evaluationData);

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::CREATE,
                    "Création d'une évaluation pour le cours : {$cours->nom}",
                    $newEvaluation,
                    null,
                    $newEvaluation->toArray()
                );

                return $newEvaluation;
            });

            // Invalidation du cache
            CacheService::forget([
                "evaluations:cours:{$cours->id}:*",
                "cours:{$cours->id}",
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json([
                'message' => 'Évaluation créée avec succès',
                'evaluation' => new EvaluationResource($evaluation->load(['typeEvaluation', 'semestre', 'salle'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour une évaluation
     */
    public function update(UpdateEvaluationRequest $request, Evaluation $evaluation)
    {
        try {
            $oldCoursId = $evaluation->cours_id;
            $oldValues = $evaluation->toArray();

            DB::transaction(function () use ($request, $evaluation, $oldValues) {
                $evaluation->update($request->validated());

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Mise à jour de l'évaluation : {$evaluation->titre}",
                    $evaluation,
                    $oldValues,
                    $evaluation->fresh()->toArray()
                );
            });

            $newCoursId = $evaluation->cours_id;

            // Invalidation des caches
            CacheService::forget([
                "evaluations:cours:{$oldCoursId}:*",
                "cours:{$oldCoursId}",
                "evaluations:cours:{$newCoursId}:*",
                "cours:{$newCoursId}",
                "evaluation:{$evaluation->id}",
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json([
                'message' => 'Évaluation mise à jour avec succès',
                'evaluation' => new EvaluationResource($evaluation->fresh()->load(['typeEvaluation', 'semestre', 'salle'])),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer une évaluation
     */
    public function destroy(Evaluation $evaluation)
    {
        try {
            $coursId = $evaluation->cours_id;
            $evaluationId = $evaluation->id;
            $oldData = $evaluation->toArray();

            DB::transaction(function () use ($evaluation, $oldData) {
                // --- LOG SERVICE --- (Avant suppression)
                LogService::write(
                    ActionLog::DELETE,
                    "Suppression de l'évaluation : {$evaluation->titre}",
                    $evaluation,
                    $oldData
                );

                $evaluation->delete();
            });

            CacheService::forget([
                "evaluations:cours:{$coursId}:*",
                "cours:{$coursId}",
                "evaluation:{$evaluationId}",
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json(['message' => 'Évaluation supprimée avec succès']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste toutes les évaluations (Admin global)
     */
    public function all(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $typeId = $request->get('type_evaluation_id');
        $statut = $request->get('statut');

        $filterHash = md5(json_encode($request->only(['search', 'type_evaluation_id', 'statut', 'per_page'])));
        $cacheKey = "evaluations:all:page:{$page}:filters:{$filterHash}";

        $evaluations = Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($search, $typeId, $statut, $perPage) {
            $query = Evaluation::with(['cours', 'typeEvaluation', 'semestre']);

            if ($search) {
                $query->where('titre', 'like', "%{$search}%");
            }
            if ($typeId) {
                $query->where('type_evaluation_id', $typeId);
            }
            if ($statut) {
                $query->where('statut', $statut);
            }

            return $query->latest()->paginate($perPage);
        });

        return EvaluationResource::collection($evaluations);
    }
}