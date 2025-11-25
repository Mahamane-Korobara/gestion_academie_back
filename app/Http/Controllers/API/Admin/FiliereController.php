<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateFiliereRequest;
use App\Http\Requests\Admin\UpdateFiliereRequest;
use App\Http\Resources\Admin\FiliereResource;
use App\Models\Filiere;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class FiliereController extends Controller
{
    /**
     * Liste des filières (avec cache)
     */
    public function index()
    {
        return Cache::remember(CacheService::KEYS['filieres'], CacheService::DEFAULT_TTL, function () {
            $filieres = Filiere::withCount(['niveaux', 'etudiants'])
                ->with('niveaux')
                ->orderBy('nom')
                ->get();

            return FiliereResource::collection($filieres);
        });
    }

    /**
     * Détails d'une filière
     */
    public function show(Filiere $filiere)
    {
        $cacheKey = CacheService::key('filiere', $filiere->id);

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($filiere) {
            $filiere->loadCount(['niveaux', 'etudiants'])->load('niveaux.cours');
            return new FiliereResource($filiere);
        });
    }

    /**
     * Créer une filière
     */
    public function store(CreateFiliereRequest $request)
    {
        $filiere = Filiere::create($request->validated());

        // Invalider le cache
        CacheService::forgetFilieres();

        return response()->json([
            'message' => 'Filière créée avec succès',
            'filiere' => new FiliereResource($filiere),
        ], 201);
    }

    /**
     * Mettre à jour une filière
     */
    public function update(UpdateFiliereRequest $request, Filiere $filiere)
    {
        $filiere->update($request->validated());

        // Invalider les caches
        CacheService::forgetFilieres();

        return response()->json([
            'message' => 'Filière mise à jour avec succès',
            'filiere' => new FiliereResource($filiere->fresh()),
        ]);
    }

    /**
     * Supprimer une filière
     */
    public function destroy(Filiere $filiere)
    {
        // Vérifier s'il y a des étudiants
        if ($filiere->etudiants()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une filière avec des étudiants inscrits',
            ], 422);
        }

        $filiere->delete();

        // Invalider les caches
        CacheService::forgetFilieres();

        return response()->json([
            'message' => 'Filière supprimée avec succès',
        ]);
    }
}
