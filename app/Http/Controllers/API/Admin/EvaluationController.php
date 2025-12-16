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
        $cacheKey = "evaluations:cours:{$cours->id}:page:{$request->get('page', 1)}:per_page:$perPage";

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($cours, $perPage) {
            $evaluations = $cours->evaluations()
                ->with(['typeEvaluation', 'semestre', 'salle'])
                ->latest('date_evaluation')
                ->paginate($perPage);

            return EvaluationResource::collection($evaluations);
        });
    }

    /**
     * Détails d'une évaluation
     */
    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['cours', 'typeEvaluation', 'semestre', 'salle']);
        return new EvaluationResource($evaluation);
    }

    /**
     * Créer une évaluation
     */
    public function store(CreateEvaluationRequest $request, Cours $cours)
    {
        $evaluationData = $request->validated();
        $evaluationData['cours_id'] = $cours->id;
        $evaluation = Evaluation::create($evaluationData);


        // Invalider les caches
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
        $evaluation->update($request->validated());
        $newCoursId = $evaluation->cours_id;

        CacheService::forget([
            "evaluations:cours:{$oldCoursId}:*",
            "cours:{$oldCoursId}",
            "evaluations:cours:{$newCoursId}:*",
            "cours:{$newCoursId}",
            "evaluation:{$evaluation->id}",
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
        $evaluation->delete();

        CacheService::forget([
            "evaluations:cours:{$coursId}:*",
            "cours:{$coursId}",
            "evaluation:{$evaluation->id}",
        ]);

        return response()->json(['message' => 'Évaluation supprimée avec succès']);
    }

    /**
     * Liste toutes les évaluations (admin global)
     */
    public function all(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $typeId = $request->get('type_evaluation_id');
        $statut = $request->get('statut');

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

        $evaluations = $query->latest()->paginate($perPage);
        return EvaluationResource::collection($evaluations);
    }
}