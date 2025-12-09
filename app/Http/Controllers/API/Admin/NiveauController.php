<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateNiveauRequest;
use App\Http\Requests\Admin\UpdateNiveauRequest;
use App\Http\Resources\Admin\NiveauResource;
use App\Models\Niveau;
use App\Models\Filiere;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NiveauController extends Controller
{
    /**
     * Liste des niveaux d'une filière (avec cache)
     */
    public function index(Request $request)
    {
        $filiereId = $request->get('filiere_id');

        if (!$filiereId) {
            return response()->json([
                'message' => 'Le paramètre filiere_id est requis',
            ], 422);
        }

        $cacheKey = CacheService::key('niveaux', $filiereId);

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($filiereId) {
            $niveaux = Niveau::where('filiere_id', $filiereId)
                ->withCount(['cours', 'etudiants'])
                ->orderBy('ordre')
                ->get();

            return NiveauResource::collection($niveaux);
        });
    }

    /**
     * Tous les niveaux (sans filtre)
     */
    public function all()
    {
        return Cache::remember('niveaux:all', CacheService::DEFAULT_TTL, function () {
            $niveaux = Niveau::with('filiere')
                ->withCount(['cours', 'etudiants'])
                ->orderBy('ordre')
                ->get();

            return NiveauResource::collection($niveaux);
        });
    }

    /**
     * Détails d'un niveau
     */
    public function show(Niveau $niveau)
    {
        $cacheKey = sprintf('niveau:%d', $niveau->id);

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($niveau) {
            $niveau->load('filiere')->loadCount(['cours', 'etudiants']);
            return new NiveauResource($niveau);
        });
    }

    /**
     * Créer un niveau
     */
    public function store(CreateNiveauRequest $request)
    {
        $niveau = Niveau::create($request->validated());

        // Invalider les caches
        CacheService::forgetFilieres();
        CacheService::forget([
            CacheService::key('niveaux', $niveau->filiere_id),
            'niveaux:all',
        ]);

        return response()->json([
            'message' => 'Niveau créé avec succès',
            'niveau' => new NiveauResource($niveau->load('filiere')),
        ], 201);
    }

    /**
     * Mettre à jour un niveau
     */
    public function update(UpdateNiveauRequest $request, Niveau $niveau)
    {
        $niveau->update($request->validated());

        // Invalider les caches
        CacheService::forgetFilieres();
        CacheService::forget([
            CacheService::key('niveaux', $niveau->filiere_id),
            'niveaux:all',
            sprintf('niveau:%d', $niveau->id),
        ]);

        return response()->json([
            'message' => 'Niveau mis à jour avec succès',
            'niveau' => new NiveauResource($niveau->fresh()->load('filiere')),
        ]);
    }

    /**
     * Supprimer un niveau
     */
    public function destroy(Niveau $niveau)
    {
        // Vérifier s'il y a des étudiants
        if ($niveau->etudiants()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un niveau avec des étudiants inscrits',
                'etudiants_count' => $niveau->etudiants()->count(),
            ], 422);
        }

        // Vérifier s'il y a des cours
        if ($niveau->cours()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un niveau avec des cours associés',
                'cours_count' => $niveau->cours()->count(),
            ], 422);
        }

        $filiereId = $niveau->filiere_id;
        $niveau->delete();

        // Invalider les caches
        CacheService::forgetFilieres();
        CacheService::forget([
            CacheService::key('niveaux', $filiereId),
            'niveaux:all',
        ]);

        return response()->json([
            'message' => 'Niveau supprimé avec succès',
        ]);
    }

    /**
     * Créer tous les niveaux standards pour une filière
     * (L1, L2, L3 pour Licence ou M1, M2 pour Master)
     */
    public function createStandardLevels(Request $request, Filiere $filiere)
    {
        $request->validate([
            'type' => ['required', 'in:licence,master'],
        ]);

        $niveaux = [];
        
        if ($request->type === 'licence') {
            $niveaux = [
                ['nom' => 'L1', 'ordre' => 1],
                ['nom' => 'L2', 'ordre' => 2],
                ['nom' => 'L3', 'ordre' => 3],
            ];
        } else {
            $niveaux = [
                ['nom' => 'M1', 'ordre' => 1],
                ['nom' => 'M2', 'ordre' => 2],
            ];
        }

        $created = [];
        foreach ($niveaux as $niveau) {
            // Vérifier si le niveau n'existe pas déjà
            $exists = Niveau::where('filiere_id', $filiere->id)
                ->where('nom', $niveau['nom'])
                ->exists();

            if (!$exists) {
                $created[] = Niveau::create([
                    'filiere_id' => $filiere->id,
                    'nom' => $niveau['nom'],
                    'ordre' => $niveau['ordre'],
                    'nombre_semestres' => 2,
                ]);
            }
        }

        // Invalider les caches
        CacheService::forgetFilieres();
        CacheService::forget([
            CacheService::key('niveaux', $filiere->id),
            'niveaux:all',
        ]);

        return response()->json([
            'message' => count($created) . ' niveau(x) créé(s) avec succès',
            'niveaux' => NiveauResource::collection(collect($created)),
        ], 201);
    }
}