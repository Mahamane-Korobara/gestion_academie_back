<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateEvaluationRequest;
use App\Http\Requests\Admin\UpdateEvaluationRequest;
use App\Http\Resources\Admin\EvaluationResource;
use App\Models\Evaluation;
use App\Models\Cours;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    /**
     * Liste des évaluations d'un cours
     */
    public function index(Request $request, Cours $cours)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $cacheKey = "evaluations:cours:{$cours->id}:page:{$page}:per_page:$perPage";

        // Ici on met en cache la collection brute pour éviter les soucis de sérialisation des Resources
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
        // On peut aussi mettre en cache un détail d'évaluation
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
        $evaluation = DB::transaction(function () use ($request, $cours) {
            $evaluationData = $request->validated();
            $evaluationData['cours_id'] = $cours->id;
            
            return Evaluation::create($evaluationData);
        });

        // On invalide le cache APRES la transaction réussie
        CacheService::forget([
            "evaluations:cours:{$cours->id}:*",
            "cours:{$cours->id}",
            CacheService::KEYS['stats_dashboard'],
        ]);

        return response()->json([
            'message' => 'Évaluation créée avec succès',
            'evaluation' => new EvaluationResource($evaluation->load(['typeEvaluation', 'semestre', 'salle'])),
        ], 201);
    }

    /**
     * Mettre à jour une évaluation
     */
    public function update(UpdateEvaluationRequest $request, Evaluation $evaluation)
    {
        $oldCoursId = $evaluation->cours_id;

        DB::transaction(function () use ($request, $evaluation) {
            $evaluation->update($request->validated());
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
    }

    /**
     * Supprimer une évaluation
     */
    public function destroy(Evaluation $evaluation)
    {
        $coursId = $evaluation->cours_id;
        $evaluationId = $evaluation->id;

        DB::transaction(function () use ($evaluation) {
            $evaluation->delete();
        });

        CacheService::forget([
            "evaluations:cours:{$coursId}:*",
            "cours:{$coursId}",
            "evaluation:{$evaluationId}",
            CacheService::KEYS['stats_dashboard'],
        ]);

        return response()->json(['message' => 'Évaluation supprimée avec succès']);
    }

    /**
     * Liste toutes les évaluations
     */
    public function all(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $typeId = $request->get('type_evaluation_id');
        $statut = $request->get('statut');

        // Création d'une clé de cache basée sur les filtres
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