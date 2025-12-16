<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NoteController extends Controller
{

    use AuthorizesRequests;
    /**
     * Saisir les notes pour une évaluation
     */
    public function store(Request $request, Evaluation $evaluation)
    {
        // Vérification de la policy
        $this->authorize('saisirNotes', $evaluation);

        $request->validate([
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:etudiants,id',
            'notes.*.note' => 'nullable|numeric|min:0|max:20',
            'notes.*.is_absent' => 'boolean',
            'notes.*.commentaire' => 'nullable|string|max:255',
        ]);

        $userId = $request->user()->id;

        // Récupérer en une seule requête tous les étudiants inscrits au cours
        $inscrits = Inscription::where('cours_id', $evaluation->cours_id)
                               ->pluck('etudiant_id')
                               ->toArray();

        $saisies = [];

        foreach ($request->notes as $item) {
            // Ignorer les étudiants non inscrits
            if (!in_array($item['etudiant_id'], $inscrits)) {
                continue;
            }

            $saisies[] = [
                'etudiant_id'    => $item['etudiant_id'],
                'evaluation_id'  => $evaluation->id,
                'note'           => $item['note'] ?? null,
                'is_absent'      => $item['is_absent'] ?? false,
                'commentaire'    => $item['commentaire'] ?? null,
                'statut'         => 'brouillon',
                'saisi_par'      => $userId,
                'date_saisie'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        try {
            DB::transaction(function () use ($evaluation, $saisies) {
                // Supprimer les notes existantes pour cette évaluation
                Note::where('evaluation_id', $evaluation->id)->delete();

                // Insérer les nouvelles en batch
                if (!empty($saisies)) {
                    Note::insert($saisies);
                }
            });

            return response()->json([
                'message' => 'Notes enregistrées en brouillon',
                'count' => count($saisies),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la saisie des notes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
