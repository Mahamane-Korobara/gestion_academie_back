<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateInscriptionRequest;
use App\Http\Requests\Admin\InscriptionMasseRequest;
use App\Http\Requests\Admin\InscriptionNiveauRequest;
use App\Http\Resources\Admin\InscriptionResource;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Cours;
use App\Models\Semestre;
use App\Models\AnneeAcademique;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InscriptionController extends Controller
{
    /**
     * Inscription manuelle d'un étudiant à des cours spécifiques
     */
    public function store(CreateInscriptionRequest $request)
    {
        try {
            DB::beginTransaction();

            $inscriptions = [];
            $semestre = Semestre::findOrFail($request->semestre_id);
            $etudiant = Etudiant::with('user')->findOrFail($request->etudiant_id);

            foreach ($request->cours_ids as $coursId) {
                $inscriptions[] = [
                    'etudiant_id' => $request->etudiant_id,
                    'cours_id' => $coursId,
                    'semestre_id' => $request->semestre_id,
                    'annee_academique_id' => $semestre->annee_academique_id,
                    'date_inscription' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Inscription::insertOrIgnore($inscriptions);

            // --- LOG ACTIVITÉ ---
            LogService::write(
                ActionLog::CREATE,
                "Inscription manuelle de l'étudiant {$etudiant->user->name} à " . count($inscriptions) . " cours.",
                $etudiant,
                null,
                ['cours_ids' => $request->cours_ids]
            );

            DB::commit();

            // Invalider les caches
            CacheService::forget([
                'inscriptions:*',
                "inscriptions:etudiant:{$etudiant->id}",
                CacheService::key('etudiants_filiere', '*'),
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json([
                'message' => 'Inscription manuelle réussie',
                'count' => count($inscriptions),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'inscription manuelle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inscription automatique d'un étudiant à tous les cours de son niveau/semestre
     */
    public function inscrireCoursNiveau(InscriptionNiveauRequest $request, Etudiant $etudiant)
    {
        try {
            DB::beginTransaction();

            $anneeActive = AnneeAcademique::active()->first();
            $semestreActif = Semestre::active()
                ->where('annee_academique_id', $anneeActive->id)
                ->first();

            if ($etudiant->annee_academique_id !== $anneeActive->id) {
                return response()->json([
                    'message' => 'L\'étudiant n\'appartient pas à l\'année académique active.'
                ], 422);
            }

            $cours = Cours::where('niveau_id', $etudiant->niveau_id)
                ->where('semestre_id', $semestreActif->id)
                ->where('annee_academique_id', $anneeActive->id)
                ->get();

            if ($cours->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun cours trouvé pour ce niveau et semestre.',
                ], 404);
            }

            $inscriptions = [];
            foreach ($cours as $c) {
                $inscriptions[] = [
                    'etudiant_id' => $etudiant->id,
                    'cours_id' => $c->id,
                    'semestre_id' => $semestreActif->id,
                    'annee_academique_id' => $anneeActive->id,
                    'date_inscription' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Inscription::insertOrIgnore($inscriptions);

            // --- LOG ACTIVITÉ ---
            LogService::write(
                ActionLog::CREATE,
                "Inscription automatique de {$etudiant->user->name} pour tous les cours du niveau {$etudiant->niveau->nom}.",
                $etudiant
            );

            DB::commit();

            CacheService::forget([
                'inscriptions:*',
                "inscriptions:etudiant:{$etudiant->id}",
                CacheService::key('etudiants_filiere', $etudiant->filiere_id),
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json([
                'message' => 'Inscription automatique réussie',
                'cours_inscrits' => count($inscriptions),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'inscription automatique',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inscription de masse : tous les étudiants d'un niveau/filière
     */
    public function inscriptionMasse(InscriptionMasseRequest $request)
    {
        try {
            DB::beginTransaction();

            $anneeActive = AnneeAcademique::active()->first();
            $semestre = Semestre::findOrFail($request->semestre_id);

            $etudiants = Etudiant::where('filiere_id', $request->filiere_id)
                ->where('niveau_id', $request->niveau_id)
                ->where('annee_academique_id', $anneeActive->id)
                ->get();

            $cours = Cours::where('niveau_id', $request->niveau_id)
                ->where('semestre_id', $request->semestre_id)
                ->where('annee_academique_id', $anneeActive->id)
                ->get();

            if ($etudiants->isEmpty()) {
                return response()->json(['message' => 'Aucun étudiant trouvé.'], 404);
            }
            if ($cours->isEmpty()) {
                return response()->json(['message' => 'Aucun cours trouvé.'], 404);
            }

            $inscriptions = [];
            foreach ($etudiants as $etudiant) {
                foreach ($cours as $c) {
                    $inscriptions[] = [
                        'etudiant_id' => $etudiant->id,
                        'cours_id' => $c->id,
                        'semestre_id' => $semestre->id,
                        'annee_academique_id' => $anneeActive->id,
                        'date_inscription' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            Inscription::insertOrIgnore($inscriptions);

            // --- LOG ACTIVITÉ ---
            LogService::write(
                ActionLog::CREATE,
                "Inscription de masse : " . $etudiants->count() . " étudiants inscrits à " . $cours->count() . " cours.",
                $semestre
            );

            DB::commit();

            CacheService::forget([
                'inscriptions:*',
                CacheService::key('etudiants_filiere', $request->filiere_id),
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json([
                'message' => 'Inscription de masse réussie',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'inscription de masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste toutes les inscriptions
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $cacheKey = "inscriptions:all:page:{$request->get('page', 1)}:per_page:$perPage";

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($perPage) {
            $inscriptions = Inscription::with([
                'etudiant.user',
                'cours',
                'semestre.anneeAcademique',
            ])->latest()->paginate($perPage);

            return InscriptionResource::collection($inscriptions);
        });
    }

    /**
     * Inscriptions d'un étudiant
     */
    public function parEtudiant($etudiantId)
    {
        $cacheKey = "inscriptions:etudiant:$etudiantId";

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($etudiantId) {
            $inscriptions = Inscription::with(['etudiant.user', 'cours', 'semestre.anneeAcademique'])
                ->where('etudiant_id', $etudiantId)
                ->orderBy('date_inscription', 'desc')
                ->get();

            return InscriptionResource::collection($inscriptions);
        });
    }

    /**
     * Étudiants inscrits à un cours
     */
    public function parCours($coursId)
    {
        $cacheKey = "inscriptions:cours:$coursId";

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($coursId) {
            $inscriptions = Inscription::with(['etudiant.user', 'semestre'])
                ->where('cours_id', $coursId)
                ->get();

            return InscriptionResource::collection($inscriptions);
        });
    }

    /**
     * Supprimer une inscription
     */
    public function destroy(Inscription $inscription)
    {
        try {
            DB::beginTransaction();
            
            $filiereId = $inscription->etudiant->filiere_id;
            $etudiantId = $inscription->etudiant_id;
            $coursNom = $inscription->cours->nom;
            $etudiantNom = $inscription->etudiant->user->name;

            // --- LOG ACTIVITÉ ---
            LogService::write(
                ActionLog::DELETE,
                "Désinscription de l'étudiant {$etudiantNom} du cours {$coursNom}.",
                $inscription
            );

            $inscription->delete();

            DB::commit();

            CacheService::forget([
                "inscriptions:etudiant:{$etudiantId}",
                "inscriptions:cours:{$inscription->cours_id}",
                'inscriptions:*',
                CacheService::key('etudiants_filiere', $filiereId),
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json(['message' => 'Inscription supprimée avec succès']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la suppression', 'error' => $e->getMessage()], 500);
        }
    }
}