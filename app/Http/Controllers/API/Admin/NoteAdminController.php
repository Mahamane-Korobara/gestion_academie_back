<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use App\Http\Resources\Admin\NoteResource;
use Illuminate\Support\Facades\DB;
use App\Models\LogActivite;
use App\Enums\ActionLog;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class NoteAdminController extends Controller
{
    use AuthorizesRequests;
    public function validerNotes(Request $request, Note $note)
    {
        // Vérification via Policy
        $this->authorize('validerNotes', $note);

        if ($note->statut === 'validee') {
            return response()->json(['message' => 'Note déjà validée'], 422);
        }

        $note->update([
            'statut' => 'validee',
            'valide_par' => $request->user()->id,
            'date_validation' => now(),
        ]);

        return response()->json([
            'message' => 'Note validée avec succès',
            'note' => $note->only(['id', 'etudiant_id', 'note', 'statut']),
        ]);
    }

    public function notesEnAttente(Request $request)
    {
        // Autorisation via Policy
        $this->authorize('toutVoir', Note::class);

        $perPage = $request->get('per_page', 20);
        $query = Note::whereIn('statut', ['brouillon', 'soumise'])
            ->with(['etudiant.user', 'evaluation.cours', 'evaluation.typeEvaluation', 'saisiPar'])
            ->orderByDesc('date_saisie');

        if ($coursId = $request->get('cours_id')) {
            $query->whereHas('evaluation', fn($q) => $q->where('cours_id', $coursId));
        }

        if ($etudiantId = $request->get('etudiant_id')) {
            $query->where('etudiant_id', $etudiantId);
        }

        // Pagination efficace
        $notes = $query->paginate($perPage);

        // Transformation via Resource
        return NoteResource::collection($notes);
    }

    public function validerMasse(Request $request)
    {
        // Vérification via Policy
        $this->authorize('validerNotes', Note::class);

        // Validation entrée
        $request->validate([
            'note_ids' => 'required|array|min:1|max:100',
            'note_ids.*' => 'exists:notes,id',
        ]);

        try {
            DB::beginTransaction();

            // Filtrer les notes valables à valider
            $notes = Note::whereIn('id', $request->note_ids)
                ->whereIn('statut', ['brouillon', 'soumise'])
                ->get();

            $count = $notes->count();

            if ($count === 0) {
                return response()->json([
                    'message' => 'Aucune note à valider',
                    'validated_count' => 0,
                ]);
            }

            // Mise à jour en masse
            $now = now();
            $userId = $request->user()->id;

            $count = Note::whereIn('id', $request->note_ids)
                        ->whereIn('statut', ['brouillon', 'soumise'])
                        ->update([
                            'statut' => 'validee',
                            'valide_par' => $userId,
                            'date_validation' => $now,
                        ]);


            // Journal d'activité
            LogActivite::create([
                'user_id' => $userId,
                'action' => ActionLog::UPDATE,
                'description' => "Validation en masse de {$count} notes",
                'model_type' => Note::class,
            ]);

            DB::commit();

            // Retour optimisé via Resource
            return NoteResource::collection($notes);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la validation en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}