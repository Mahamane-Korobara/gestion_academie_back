<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateFiliereRequest;
use App\Http\Requests\Admin\UpdateFiliereRequest;
use App\Http\Resources\Admin\FiliereResource;
use App\Models\Filiere;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        try {
            $filiere = DB::transaction(function () use ($request) {
                $newFiliere = Filiere::create($request->validated());

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::CREATE,
                    "Création de la filière : {$newFiliere->nom} ({$newFiliere->code})",
                    $newFiliere,
                    null,
                    $newFiliere->toArray()
                );

                return $newFiliere;
            });

            // Invalider les caches (Filieres + Dashboard car le nombre change)
            CacheService::forgetFilieres();

            return response()->json([
                'message' => 'Filière créée avec succès',
                'filiere' => new FiliereResource($filiere),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour une filière
     */
    public function update(UpdateFiliereRequest $request, Filiere $filiere)
    {
        try {
            $oldValues = $filiere->toArray();

            DB::transaction(function () use ($request, $filiere, $oldValues) {
                $filiere->update($request->validated());

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Modification de la filière : {$filiere->nom}",
                    $filiere,
                    $oldValues,
                    $filiere->fresh()->toArray()
                );
            });

            // Invalider les caches (Clé spécifique et liste globale)
            CacheService::forgetFilieres();
            Cache::forget(CacheService::key('filiere', $filiere->id));

            return response()->json([
                'message' => 'Filière mise à jour avec succès',
                'filiere' => new FiliereResource($filiere->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer une filière
     */
    public function destroy(Filiere $filiere)
    {
        // Vérification métier avant action
        if ($filiere->etudiants()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une filière avec des étudiants inscrits',
            ], 422);
        }

        try {
            $filiereId = $filiere->id;
            $filiereNom = $filiere->nom;
            $oldData = $filiere->toArray();

            DB::transaction(function () use ($filiere, $oldData, $filiereNom) {
                // --- LOG SERVICE --- (Avant suppression)
                LogService::write(
                    ActionLog::DELETE,
                    "Suppression de la filière : {$filiereNom}",
                    $filiere,
                    $oldData
                );

                $filiere->delete();
            });

            // Invalider les caches
            CacheService::forgetFilieres();
            Cache::forget(CacheService::key('filiere', $filiereId));

            return response()->json([
                'message' => 'Filière supprimée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }
}