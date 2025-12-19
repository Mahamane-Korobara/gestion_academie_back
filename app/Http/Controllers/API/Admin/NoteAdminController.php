<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use App\Http\Resources\Admin\NoteResource;
use App\Services\CalculAcademique;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NoteAdminController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CalculAcademique $calculAcademique
    ) {}

    /**
     * Valider une note 
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

        $oldValues = $note->toArray();

        DB::transaction(function () use ($note, $request, $oldValues) {
            // Validation de la note
            $note->update([
                'statut' => 'validee',
                'valide_par' => $request->user()->id,
                'date_validation' => now(),
            ]);

            // --- LOG SERVICE ---
            LogService::write(
                ActionLog::UPDATE,
                "Validation individuelle de la note ID: {$note->id} pour l'étudiant: {$note->etudiant->user->name}",
                $note,
                $oldValues,
                $note->fresh()->toArray()
            );

            // Recalcul de la moyenne du semestre
            $this->calculAcademique->calculerMoyenneSemestre(
                $note->etudiant,
                $note->evaluation->semestre,
                $request->user()->id
            );
        });

        // Invalider le cache du bulletin de cet étudiant
        CacheService::forgetBulletins($note->etudiant_id);
        // Invalider les listes de notes en attente
        CacheService::forget('notes:en_attente:*');

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
        $this->authorize('toutVoir', Note::class);

        $perPage = $request->integer('per_page', 20);
        $page = $request->get('page', 1);

        $filters = md5(json_encode([
            'cours_id' => $request->get('cours_id'),
            'etudiant_id' => $request->get('etudiant_id'),
            'per_page' => $perPage
        ]));
        
        $cacheKey = "notes:en_attente:page:{$page}:filters:{$filters}";

        $notes = Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($request, $perPage) {
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

            return $query->paginate($perPage);
        });

        return NoteResource::collection($notes);
    }

    /**
     * Valider plusieurs notes en masse
     */
    public function validerMasse(Request $request)
    {
        $this->authorize('validerNotes', Note::class);

        $request->validate([
            'note_ids' => 'required|array|min:1|max:100',
            'note_ids.*' => 'exists:notes,id',
        ]);

        $userId = $request->user()->id;
        $now = now();

        $notesAValider = Note::whereIn('id', $request->note_ids)
            ->whereIn('statut', ['brouillon', 'soumise'])
            ->get();

        if ($notesAValider->isEmpty()) {
            return response()->json(['message' => 'Aucune note éligible à la validation'], 404);
        }

        DB::transaction(function () use ($request, $userId, $now, $notesAValider) {
            Note::whereIn('id', $request->note_ids)
                ->whereIn('statut', ['brouillon', 'soumise'])
                ->update([
                    'statut' => 'validee',
                    'valide_par' => $userId,
                    'date_validation' => $now,
                ]);

            // --- LOG SERVICE (MASSE) ---
            LogService::write(
                ActionLog::UPDATE,
                "Validation en masse de " . $notesAValider->count() . " notes par l'administrateur.",
                null, // Pas de modèle unique pour une action de masse
                ['note_ids' => $request->note_ids],
                ['statut' => 'validee']
            );
        });

        $dejaCalcule = [];

        foreach ($notesAValider->load(['etudiant', 'evaluation.semestre']) as $note) {
            $key = $note->etudiant_id . '_' . $note->evaluation->semestre_id;

            if (isset($dejaCalcule[$key])) {
                continue;
            }

            $this->calculAcademique->calculerMoyenneSemestre(
                $note->etudiant,
                $note->evaluation->semestre,
                $userId
            );

            CacheService::forgetBulletins($note->etudiant_id);
            $dejaCalcule[$key] = true;
        }

        CacheService::forget('notes:en_attente:*');

        return NoteResource::collection($notesAValider->fresh());
    }
}