<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCoursRequest;
use App\Http\Resources\Admin\CoursResource;
use App\Models\Cours;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CoursController extends Controller
{
    /**
     * Liste des cours (avec cache)
     */
    public function index(Request $request)
    {
        $niveauId = $request->get('niveau_id');
        $semestre = $request->get('semestre');

        $cacheKey = sprintf('cours:list:niveau:%s:semestre:%s', 
            $niveauId ?? 'all', 
            $semestre ?? 'all'
        );

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($request, $niveauId, $semestre) {
            $query = Cours::with(['niveau.filiere', 'professeurs'])
                ->withCount('inscriptions');

            if ($niveauId) {
                $query->where('niveau_id', $niveauId);
            }

            if ($semestre) {
                $query->where('semestre', $semestre);
            }

            $cours = $query->latest()->get();

            return CoursResource::collection($cours);
        });
    }

    /**
     * Créer un cours
     */
    public function store(CreateCoursRequest $request)
    {
        try {
            DB::beginTransaction();

            $cours = Cours::create($request->except('professeur_ids'));

            // Assigner les professeurs
            if ($request->filled('professeur_ids')) {
                $cours->professeurs()->attach(
                    $request->professeur_ids,
                    ['annee_academique_id' => $request->annee_academique_id]
                );
            }

            DB::commit();

            // Invalider les caches
            CacheService::forgetCours();

            return response()->json([
                'message' => 'Cours créé avec succès',
                'cours' => new CoursResource($cours->load('professeurs')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création du cours',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'un cours
     */
    public function show(Cours $cours)
    {
        $cours->load(['niveau.filiere', 'professeurs', 'evaluations']);
        return new CoursResource($cours);
    }

    /**
     * Supprimer un cours
     */
    public function destroy(Cours $cours)
    {
        if ($cours->inscriptions()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un cours avec des inscriptions',
            ], 422);
        }

        $cours->delete();

        // Invalider les caches
        CacheService::forgetCours();

        return response()->json([
            'message' => 'Cours supprimé avec succès',
        ]);
    }
}