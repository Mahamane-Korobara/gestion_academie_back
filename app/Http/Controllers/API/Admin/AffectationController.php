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

class AffectationController extends Controller
{
    /**
     * Affecter un ou plusieurs professeurs à un cours
     */
    public function affecterProfesseurs(Request $request, Cours $cours)
    {
        $request->validate([
            'professeur_ids' => 'required|array|min:1',
            'professeur_ids.*' => 'exists:professeurs,id',
        ]);

        try {
            DB::beginTransaction();

            $anneeId = $cours->annee_academique_id;

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
            $professeurs = Professeur::whereIn('id', $request->professeur_ids)->get();
            foreach ($professeurs as $prof) {
                $cours->professeurs()->attach($prof->id, ['annee_academique_id' => $anneeId]);
            }

            LogService::write(
                ActionLog::UPDATE,
                "Nouvelle affectation de professeurs pour le cours : {$cours->nom}",
                $cours,
                ['professeur_ids' => $oldProfIds], // Valeurs avant
                ['professeur_ids' => $request->professeur_ids] // Valeurs après
            );

            DB::commit();

            // Invalider le cache (Cours spécifique, liste profs et Dashboard)
            CacheService::forget([
                "cours:{$cours->id}",
                'professeurs:*',
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json([
                'message' => 'Professeurs affectés au cours avec succès',
                'professeurs' => $professeurs->map(fn($p) => [
                    'id' => $p->id,
                    'nom_complet' => $p->nom_complet,
                ]),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'affectation des professeurs',
                'error' => $e->getMessage(),
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

            // Action de retrait
            $cours->professeurs()
                ->wherePivot('annee_academique_id', $cours->annee_academique_id)
                ->detach($professeur->id);

            LogService::write(
                ActionLog::DELETE,
                "Retrait du professeur {$professeur->nom_complet} du cours {$cours->nom}",
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

            return response()->json([
                'message' => 'Professeur retiré du cours avec succès',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors du retrait du professeur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}