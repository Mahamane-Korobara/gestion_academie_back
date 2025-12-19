<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateNiveauRequest;
use App\Http\Requests\Admin\UpdateNiveauRequest;
use App\Http\Resources\Admin\NiveauResource;
use App\Models\Niveau;
use App\Models\Filiere;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        try {
            return DB::transaction(function () use ($request) {
                $niveau = Niveau::create($request->validated());

                // --- LOG ACTIVITÉ ---
                LogService::write(
                    ActionLog::CREATE,
                    "Création du niveau : {$niveau->nom} pour la filière ID: {$niveau->filiere_id}",
                    $niveau,
                    null,
                    $niveau->toArray()
                );

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
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour un niveau
     */
    public function update(UpdateNiveauRequest $request, Niveau $niveau)
    {
        try {
            return DB::transaction(function () use ($request, $niveau) {
                $oldValues = $niveau->toArray();
                $niveau->update($request->validated());

                // --- LOG ACTIVITÉ ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Mise à jour du niveau : {$niveau->nom}",
                    $niveau,
                    $oldValues,
                    $niveau->fresh()->toArray()
                );

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
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un niveau
     */
    public function destroy(Niveau $niveau)
    {
        // Vérifications métier
        if ($niveau->etudiants()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un niveau avec des étudiants inscrits',
                'etudiants_count' => $niveau->etudiants()->count(),
            ], 422);
        }

        if ($niveau->cours()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un niveau avec des cours associés',
                'cours_count' => $niveau->cours()->count(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($niveau) {
                $filiereId = $niveau->filiere_id;
                $nomNiveau = $niveau->nom;
                $oldData = $niveau->toArray();

                // --- LOG ACTIVITÉ ---
                LogService::write(
                    ActionLog::DELETE,
                    "Suppression du niveau : {$nomNiveau}",
                    $niveau,
                    $oldData
                );

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
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Créer tous les niveaux standards pour une filière
     */
    public function createStandardLevels(Request $request, Filiere $filiere)
    {
        $request->validate([
            'type' => ['required', 'in:licence,master'],
        ]);

        try {
            return DB::transaction(function () use ($request, $filiere) {
                $niveauxDef = [];
                
                if ($request->type === 'licence') {
                    $niveauxDef = [
                        ['nom' => 'L1', 'ordre' => 1],
                        ['nom' => 'L2', 'ordre' => 2],
                        ['nom' => 'L3', 'ordre' => 3],
                    ];
                } else {
                    $niveauxDef = [
                        ['nom' => 'M1', 'ordre' => 1],
                        ['nom' => 'M2', 'ordre' => 2],
                    ];
                }

                $created = [];
                foreach ($niveauxDef as $def) {
                    $exists = Niveau::where('filiere_id', $filiere->id)
                        ->where('nom', $def['nom'])
                        ->exists();

                    if (!$exists) {
                        $created[] = Niveau::create([
                            'filiere_id' => $filiere->id,
                            'nom' => $def['nom'],
                            'ordre' => $def['ordre'],
                            'nombre_semestres' => 2,
                        ]);
                    }
                }

                // --- LOG ACTIVITÉ ---
                if (count($created) > 0) {
                    LogService::write(
                        ActionLog::CREATE,
                        "Génération automatique de " . count($created) . " niveaux standard ({$request->type}) pour la filière : {$filiere->nom}",
                        $filiere
                    );
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
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }
}