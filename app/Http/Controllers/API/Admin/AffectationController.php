<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Professeur;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffectationController extends Controller
{
    /**
     * Affecter un ou plusieurs professeurs à un cours
     */
    public function affecterProfesseurs(Request $request, Cours $cours)
    {
        $validated = $request->validate([
            'professeur_ids' => 'required|array|min:1',
            'professeur_ids.*' => 'exists:professeurs,id',
        ]);

        try {
            DB::beginTransaction();

            $anneeId = $cours->annee_academique_id;
            
            if (!$anneeId) {
                // Si le cours n'a pas d'année académique, utiliser l'année active
                $anneeActive = \App\Models\AnneeAcademique::where('is_active', true)->first();
                
                if (!$anneeActive) {
                    return response()->json([
                        'message' => 'Aucune année académique active trouvée',
                    ], 422);
                }
                
                $anneeId = $anneeActive->id;
                
                $cours->update(['annee_academique_id' => $anneeId]);
            }

            // Récupérer les anciens IDs pour le suivi dans les logs avant suppression
            $oldProfIds = $cours->professeurs()
                ->wherePivot('annee_academique_id', $anneeId)
                ->pluck('professeurs.id')
                ->toArray();

            // Nettoyer les anciennes affectations (remplacement complet)
            DB::table('cours_professeur')
                ->where('cours_id', $cours->id)
                ->where('annee_academique_id', $anneeId)
                ->delete();

            // Affecter les nouveaux professeurs
            $professeurs = Professeur::whereIn('id', $validated['professeur_ids'])->get();
            
            if ($professeurs->count() !== count($validated['professeur_ids'])) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Un ou plusieurs professeurs introuvables',
                ], 422);
            }

            foreach ($professeurs as $prof) {
                $cours->professeurs()->attach($prof->id, ['annee_academique_id' => $anneeId]);
            }

            LogService::write(
                ActionLog::UPDATE,
                "Nouvelle affectation de professeurs pour le cours : {$cours->titre}",
                $cours,
                ['professeur_ids' => $oldProfIds],
                ['professeur_ids' => $validated['professeur_ids']]
            );

            DB::commit();

            // Invalider le cache
            CacheService::forget([
                "cours:{$cours->id}",
                'professeurs:*',
                CacheService::KEYS['stats_dashboard'],
            ]);

            $cours->load(['professeurs', 'niveau', 'semestre']);

            return response()->json([
                'message' => 'Professeurs affectés au cours avec succès',
                'data' => [
                    'cours' => $cours,
                    'professeurs' => $professeurs->map(fn($p) => [
                        'id' => $p->id,
                        'nom_complet' => $p->nom_complet,
                        'specialite' => $p->specialite,
                    ]),
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur affectation professeurs', [
                'cours_id' => $cours->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de l\'affectation des professeurs',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Retirer un professeur d'un cours
     */
    public function retirerProfesseur(Request $request, Cours $cours, Professeur $professeur)
    {
        try {
            DB::beginTransaction();

            $anneeId = $cours->annee_academique_id;
            
            if (!$anneeId) {
                $anneeActive = \App\Models\AnneeAcademique::where('is_active', true)->first();
                if (!$anneeActive) {
                    return response()->json([
                        'message' => 'Aucune année académique active trouvée',
                    ], 422);
                }
                $anneeId = $anneeActive->id;
            }

            // Action de retrait
            $deleted = $cours->professeurs()
                ->wherePivot('annee_academique_id', $anneeId)
                ->detach($professeur->id);

            if ($deleted === 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Le professeur n\'est pas affecté à ce cours',
                ], 404);
            }

            LogService::write(
                ActionLog::DELETE,
                "Retrait du professeur {$professeur->nom_complet} du cours {$cours->titre}",
                $cours,
                ['professeur_id' => $professeur->id],
                null
            );

            DB::commit();

            // Invalider le cache
            CacheService::forget([
                "cours:{$cours->id}",
                "professeur:{$professeur->id}",
                CacheService::KEYS['stats_dashboard'],
            ]);

            $cours->load(['professeurs', 'niveau', 'semestre']);

            return response()->json([
                'message' => 'Professeur retiré du cours avec succès',
                'data' => $cours,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur retrait professeur', [
                'cours_id' => $cours->id,
                'professeur_id' => $professeur->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Erreur lors du retrait du professeur',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }
}