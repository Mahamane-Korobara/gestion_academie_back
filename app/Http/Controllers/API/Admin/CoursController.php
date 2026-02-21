<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCoursRequest;
use App\Http\Resources\Admin\CoursResource;
use App\Models\Cours;
use App\Models\AnneeAcademique;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
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
        $semestreId = $request->get('semestre_id');
        //On doit filtrer par l'année active par défaut
        $anneeId = $request->get('annee_academique_id') ?? AnneeAcademique::where('is_active', true)->value('id');
        $page = $request->get('page', 1);

        // On ajoute l'année dans la clé de cache
        $cacheKey = sprintf('cours:list:annee:%s:niv:%s:sem:%s:pg:%s', 
            $anneeId, $niveauId ?? 'all', $semestreId ?? 'all', $page
        );

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($niveauId, $semestreId, $anneeId) {
            $query = Cours::with(['niveau.filiere', 'professeurs', 'semestre'])
                ->withCount('inscriptions')
                ->where('annee_academique_id', $anneeId); // Filtrage par année

            if ($niveauId) $query->where('niveau_id', $niveauId);
            if ($semestreId) $query->where('semestre_id', $semestreId);

            return CoursResource::collection($query->latest()->get());
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

            // --- LOG SERVICE ---
            LogService::write(
                ActionLog::CREATE,
                "Création du cours : {$cours->titre} (Code: {$cours->code})",
                $cours,
                null,
                $cours->toArray()
            );

            DB::commit();

            // Invalider les caches liés aux cours et au dashboard
            CacheService::forgetCours();
            CacheService::forget(CacheService::KEYS['stats_dashboard']);

            return response()->json([
                'message' => 'Cours créé avec succès',
                'cours' => new CoursResource($cours->load(['niveau.filiere', 'professeurs'])),
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
        $cacheKey = "cours:show:{$cours->id}";

        $data = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($cours) {
            return $cours->load(['niveau.filiere', 'professeurs', 'evaluations']);
        });

        return new CoursResource($data);
    }

    /**
     * Supprimer un cours
     */
    public function destroy(Cours $cours)
    {
        if ($cours->inscriptions()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un cours avec des inscriptions actives',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $nomCours = $cours->nom;
            $oldData = $cours->toArray();

            // --- LOG SERVICE --- (Avant suppression)
            LogService::write(
                ActionLog::DELETE,
                "Suppression du cours : {$nomCours}",
                $cours,
                $oldData
            );

            $cours->delete();

            DB::commit();

            // Invalider les caches
            CacheService::forgetCours();
            CacheService::forget([
                "cours:show:{$cours->id}",
                CacheService::KEYS['stats_dashboard']
            ]);

            return response()->json([
                'message' => 'Cours supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}