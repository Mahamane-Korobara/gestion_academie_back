<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Professeur;
use App\Services\CacheService;
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

            // Nettoyer les anciennes affectations (optionnel : remplacement complet)
            $cours->professeurs()->wherePivot('annee_academique_id', $anneeId)->detach();

            // Affecter les nouveaux professeurs
            $professeurs = Professeur::whereIn('id', $request->professeur_ids)->get();
            foreach ($professeurs as $prof) {
                $cours->professeurs()->attach($prof->id, ['annee_academique_id' => $anneeId]);
            }

            DB::commit();

            // Invalider le cache
            CacheService::forget([
                "cours:{$cours->id}",
                'professeurs:*',
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
    public function retirerProfesseur(Cours $cours, Professeur $professeur)
    {
        try {
            DB::beginTransaction();

            $cours->professeurs()->wherePivot('annee_academique_id', $cours->annee_academique_id)
                ->detach($professeur->id);

            DB::commit();

            CacheService::forget([
                "cours:{$cours->id}",
                "professeur:{$professeur->id}",
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