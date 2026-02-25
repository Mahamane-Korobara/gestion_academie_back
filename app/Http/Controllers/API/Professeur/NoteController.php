<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Services\LogService;
use App\Enums\ActionLog;
use App\Enums\StatutNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;

class NoteController extends Controller
{
    use AuthorizesRequests;

    /**
     * Retourner les étudiants inscrits d'une évaluation
     * avec les notes déjà saisies (si existantes).
     */
    public function show(Request $request, Evaluation $evaluation)
    {
        $this->authorize('saisirNotes', $evaluation);

        $evaluation->load([
            'cours.niveau.filiere',
            'typeEvaluation',
            'semestre.anneeAcademique',
        ]);

        $inscriptions = Inscription::query()
            ->where('cours_id', $evaluation->cours_id)
            ->with(['etudiant.user'])
            ->get()
            ->sortBy(function ($inscription) {
                $nom = $inscription->etudiant?->nom ?? '';
                $prenom = $inscription->etudiant?->prenom ?? '';
                return mb_strtolower(trim("{$nom} {$prenom}"));
            })
            ->values();

        $notes = Note::query()
            ->where('evaluation_id', $evaluation->id)
            ->get()
            ->keyBy('etudiant_id');

        $etudiants = $inscriptions->map(function ($inscription) use ($notes) {
            $etudiant = $inscription->etudiant;
            $note = $etudiant ? $notes->get($etudiant->id) : null;

            $statut = $note?->statut instanceof StatutNote
                ? $note->statut->value
                : $note?->statut;

            return [
                'etudiant_id' => $etudiant?->id,
                'matricule' => $etudiant?->matricule,
                'nom_complet' => $etudiant?->nom_complet ?? trim(($etudiant?->prenom ?? '') . ' ' . ($etudiant?->nom ?? '')),
                'note_id' => $note?->id,
                'note' => $note?->note,
                'is_absent' => (bool) ($note?->is_absent ?? false),
                'commentaire' => $note?->commentaire,
                'statut' => $statut,
                'locked' => $statut === StatutNote::VALIDEE->value,
            ];
        })->filter(fn ($item) => !empty($item['etudiant_id']))->values();

        $notesSaisies = $etudiants->filter(function ($item) {
            return $item['is_absent'] === true || $item['note'] !== null;
        })->count();

        $notesValidees = $etudiants->where('statut', StatutNote::VALIDEE->value)->count();
        $absents = $etudiants->where('is_absent', true)->count();

        return response()->json([
            'evaluation' => [
                'id' => $evaluation->id,
                'titre' => $evaluation->titre,
                'coefficient' => (float) $evaluation->coefficient,
                'date_evaluation' => $evaluation->date_evaluation,
                'statut' => $evaluation->statut,
                'cours' => [
                    'id' => $evaluation->cours?->id,
                    'titre' => $evaluation->cours?->titre,
                    'code' => $evaluation->cours?->code,
                ],
                'type_evaluation' => [
                    'id' => $evaluation->typeEvaluation?->id,
                    'nom' => $evaluation->typeEvaluation?->nom,
                ],
                'semestre' => [
                    'id' => $evaluation->semestre?->id,
                    'numero' => $evaluation->semestre?->numero,
                    'annee' => $evaluation->semestre?->anneeAcademique?->annee,
                ],
            ],
            'etudiants' => $etudiants,
            'resume' => [
                'total' => $etudiants->count(),
                'notes_saisies' => $notesSaisies,
                'notes_validees' => $notesValidees,
                'absents' => $absents,
            ],
        ]);
    }

    /**
     * Saisir les notes pour une évaluation
     */
    public function store(Request $request, Evaluation $evaluation)
    {
        // Vérification de la policy (le prof doit être celui du cours)
        $this->authorize('saisirNotes', $evaluation);

        $validated = $request->validate([
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:etudiants,id|distinct',
            'notes.*.note' => 'nullable|numeric|min:0|max:20',
            'notes.*.is_absent' => 'boolean',
            'notes.*.commentaire' => 'nullable|string|max:255',
            'soumettre' => 'sometimes|boolean',
        ]);

        $userId = $request->user()->id;
        $statutCible = $request->boolean('soumettre')
            ? StatutNote::SOUMISE->value
            : StatutNote::BROUILLON->value;

        // Récupérer les étudiants inscrits au cours pour valider la liste
        $inscrits = Inscription::where('cours_id', $evaluation->cours_id)
                               ->pluck('etudiant_id')
                               ->toArray();

        $saisies = [];
        foreach ($validated['notes'] as $index => $item) {
            if (!in_array($item['etudiant_id'], $inscrits)) {
                continue;
            }

            $isAbsent = (bool) ($item['is_absent'] ?? false);
            $note = $item['note'] ?? null;

            // Une note est obligatoire si l'étudiant n'est pas absent.
            if (!$isAbsent && ($note === null || $note === '')) {
                throw ValidationException::withMessages([
                    "notes.{$index}.note" => "La note est obligatoire si l'étudiant n'est pas absent.",
                ]);
            }

            $saisies[] = [
                'etudiant_id'    => $item['etudiant_id'],
                'evaluation_id'  => $evaluation->id,
                'note'           => $isAbsent ? null : $note,
                'is_absent'      => $isAbsent,
                'commentaire'    => $item['commentaire'] ?? null,
                'statut'         => $statutCible,
                'saisi_par'      => $userId,
                'date_saisie'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        try {
            $resultat = DB::transaction(function () use ($evaluation, $saisies) {
                // Sauvegarder l'état précédent pour le log (si nécessaire)
                $oldNotesCount = Note::where('evaluation_id', $evaluation->id)->count();

                // Préserver les notes déjà validées: elles ne doivent jamais être écrasées.
                $notesExistantes = Note::where('evaluation_id', $evaluation->id)
                    ->select('etudiant_id', 'statut')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('etudiant_id');

                $saisiesEligibles = [];
                $etudiantsIgnores = 0;

                foreach ($saisies as $ligne) {
                    $noteExistante = $notesExistantes->get($ligne['etudiant_id']);

                    if ($noteExistante && $noteExistante->statut === StatutNote::VALIDEE) {
                        $etudiantsIgnores++;
                        continue;
                    }

                    $saisiesEligibles[] = $ligne;
                }

                // Remplacement ciblé sur les étudiants envoyés, hors notes validées.
                $etudiantIds = array_column($saisiesEligibles, 'etudiant_id');

                if (!empty($etudiantIds)) {
                    Note::where('evaluation_id', $evaluation->id)
                        ->whereIn('etudiant_id', $etudiantIds)
                        ->where('statut', '!=', StatutNote::VALIDEE->value)
                        ->delete();

                    Note::insert($saisiesEligibles);
                }

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Saisie/Mise à jour des notes pour l'évaluation : {$evaluation->titre} ({$evaluation->cours->titre})",
                    $evaluation,
                    ['nb_notes_precedentes' => $oldNotesCount],
                    [
                        'nb_notes_saisies' => count($saisiesEligibles),
                        'nb_notes_ignorees_validees' => $etudiantsIgnores,
                    ]
                );

                return [
                    'count' => count($saisiesEligibles),
                    'ignored' => $etudiantsIgnores,
                ];
            });

            return response()->json([
                'message' => $statutCible === StatutNote::SOUMISE->value
                    ? 'Notes soumises avec succès'
                    : 'Notes enregistrées en brouillon avec succès',
                'count' => $resultat['count'],
                'ignored_validated' => $resultat['ignored'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la saisie des notes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
