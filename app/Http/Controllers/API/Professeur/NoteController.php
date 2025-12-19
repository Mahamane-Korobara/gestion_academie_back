<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Services\LogService;
use App\Enums\ActionLog;
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
        // Vérification de la policy (le prof doit être celui du cours)
        $this->authorize('saisirNotes', $evaluation);

        $request->validate([
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:etudiants,id',
            'notes.*.note' => 'nullable|numeric|min:0|max:20',
            'notes.*.is_absent' => 'boolean',
            'notes.*.commentaire' => 'nullable|string|max:255',
        ]);

        $userId = $request->user()->id;

        // Récupérer les étudiants inscrits au cours pour valider la liste
        $inscrits = Inscription::where('cours_id', $evaluation->cours_id)
                               ->pluck('etudiant_id')
                               ->toArray();

        $saisies = [];
        foreach ($request->notes as $item) {
            if (!in_array($item['etudiant_id'], $inscrits)) {
                continue;
            }

            $saisies[] = [
                'etudiant_id'    => $item['etudiant_id'],
                'evaluation_id'  => $evaluation->id,
                'note'           => $item['note'] ?? null,
                'is_absent'      => $item['is_absent'] ?? false,
                'commentaire'    => $item['commentaire'] ?? null,
                'statut'         => 'brouillon', // Toujours en brouillon à la saisie prof
                'saisi_par'      => $userId,
                'date_saisie'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        try {
            DB::transaction(function () use ($evaluation, $saisies, $userId) {
                // Sauvegarder l'état précédent pour le log (si nécessaire)
                $oldNotesCount = Note::where('evaluation_id', $evaluation->id)->count();

                // Supprimer les notes existantes (remplacement complet du brouillon)
                Note::where('evaluation_id', $evaluation->id)->delete();

                // Insérer les nouvelles notes en batch
                if (!empty($saisies)) {
                    Note::insert($saisies);
                }

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Saisie/Mise à jour des notes pour l'évaluation : {$evaluation->titre} ({$evaluation->cours->nom})",
                    $evaluation,
                    ['nb_notes_precedentes' => $oldNotesCount],
                    ['nb_notes_saisies' => count($saisies)]
                );
            });

            return response()->json([
                'message' => 'Notes enregistrées en brouillon avec succès',
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