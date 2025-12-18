<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use App\Http\Resources\Admin\NoteResource;
use App\Services\CalculAcademique;
use Illuminate\Support\Facades\DB;
use App\Models\LogActivite;
use App\Enums\ActionLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NoteAdminController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CalculAcademique $calculAcademique
    ) {}

    /**
     * Valider une note (unitaire)
     */
    public function validerNotes(Request $request, Note $note)
    {
        // Autorisation via Policy
        $this->authorize('validerNotes', $note);

        if ($note->statut === 'validee') {
            return response()->json([
                'message' => 'Note déjà validée'
            ], 422);
        }

        DB::transaction(function () use ($note, $request) {
            // Validation de la note
            $note->update([
                'statut' => 'validee',
                'valide_par' => $request->user()->id,
                'date_validation' => now(),
            ]);

            // Recalcul de la moyenne du semestre
            $this->calculAcademique->calculerMoyenneSemestre(
                $note->etudiant,
                $note->evaluation->semestre,
                $request->user()->id
            );
        });

        return response()->json([
            'message' => 'Note validée avec succès',
            'note' => $note->only(['id', 'etudiant_id', 'note', 'statut']),
        ]);
    }

    /**
     * Lister les notes en attente de validation
     */
    public function notesEnAttente(Request $request)
    {
        // Autorisation globale admin
        $this->authorize('toutVoir', Note::class);

        $perPage = $request->integer('per_page', 20);

        $query = Note::whereIn('statut', ['brouillon', 'soumise'])
            ->with([
                'etudiant.user',
                'evaluation.cours',
                'evaluation.typeEvaluation',
                'saisiPar'
            ])
            ->orderByDesc('date_saisie');

        if ($coursId = $request->get('cours_id')) {
            $query->whereHas('evaluation', fn ($q) =>
                $q->where('cours_id', $coursId)
            );
        }

        if ($etudiantId = $request->get('etudiant_id')) {
            $query->where('etudiant_id', $etudiantId);
        }

        return NoteResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Valider plusieurs notes en masse
     */
    public function validerMasse(Request $request)
    {
        // Autorisation admin
        $this->authorize('validerNotes', Note::class);

        $request->validate([
            'note_ids' => 'required|array|min:1|max:100',
            'note_ids.*' => 'exists:notes,id',
        ]);

        $userId = $request->user()->id;
        $now = now();


        $notesExistantes = Note::whereIn('id', $request->note_ids)
            ->whereIn('statut', ['brouillon', 'soumise'])
            ->count();

        if ($notesExistantes === 0) {
            return response()->json(['message' => 'Aucune note éligible à la validation'], 404);
        }

        DB::transaction(function () use ($request, $userId, $now) {
            Note::whereIn('id', $request->note_ids)
                ->whereIn('statut', ['brouillon', 'soumise'])
                ->update([
                    'statut' => 'validee',
                    'valide_par' => $userId,
                    'date_validation' => $now,
                ]);
        });

        // 2. Recalcul des moyennes APRÈS commit
        $notes = Note::whereIn('id', $request->note_ids)
            ->with(['etudiant', 'evaluation.semestre'])
            ->get();

        $dejaCalcule = [];

        foreach ($notes as $note) {
            $key = $note->etudiant_id . '_' . $note->evaluation->semestre_id;

            if (isset($dejaCalcule[$key])) {
                continue;
            }

            $this->calculAcademique->calculerMoyenneSemestre(
                $note->etudiant,
                $note->evaluation->semestre,
                $userId
            );

            $dejaCalcule[$key] = true;
        }


        LogActivite::create([
            'user_id' => $userId,
            'action' => ActionLog::UPDATE,
            'description' => 'Validation en masse de notes',
            'model_type' => Note::class,
        ]);

        $notesValidees = Note::whereIn('id', $request->note_ids)->get();
        return NoteResource::collection($notesValidees);
    }
}
