<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\NotesExport;
use Illuminate\Http\Request;
use App\Http\Resources\Admin\NoteResource;
use App\Services\CacheService;
use App\Services\LogService;
use App\Services\NotesExportService;
use App\Enums\ActionLog;
use App\Enums\StatutNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NoteAdminController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private NotesExportService $exportService
    ) {}

    /**
     * Lister les notes soumises (admin)
     */
    public function notesSoumises(Request $request)
    {
        $this->authorize('toutVoir', Note::class);

        $perPage = $request->integer('per_page', 50);
        $page = $request->get('page', 1);

        $filters = [
            'cours_id' => $request->get('cours_id'),
            'etudiant_id' => $request->get('etudiant_id'),
            'semestre_id' => $request->get('semestre_id'),
            'filiere_id' => $request->get('filiere_id'),
            'niveau_id' => $request->get('niveau_id'),
            'per_page' => $perPage,
        ];

        $filterHash = md5(json_encode($filters));
        $cacheKey = "notes:soumises:page:{$page}:filters:{$filterHash}";

        $notes = Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($request, $perPage) {
            $query = Note::where('statut', StatutNote::SOUMISE->value)
                ->whereHas('evaluation.typeEvaluation', fn ($q) => $q->where('code', 'EF'))
                ->with([
                    'etudiant.user',
                    'evaluation.cours.niveau.filiere',
                    'evaluation.semestre.anneeAcademique',
                    'evaluation.typeEvaluation',
                    'saisiPar'
                ])
                ->orderByDesc('date_saisie');

            if ($coursId = $request->get('cours_id')) {
                $query->whereHas('evaluation', fn ($q) => $q->where('cours_id', $coursId));
            }

            if ($etudiantId = $request->get('etudiant_id')) {
                $query->where('etudiant_id', $etudiantId);
            }

            if ($semestreId = $request->get('semestre_id')) {
                $query->whereHas('evaluation', fn ($q) => $q->where('semestre_id', $semestreId));
            }

            if ($filiereId = $request->get('filiere_id')) {
                $query->whereHas('etudiant', fn ($q) => $q->where('filiere_id', $filiereId));
            }

            if ($niveauId = $request->get('niveau_id')) {
                $query->whereHas('etudiant', fn ($q) => $q->where('niveau_id', $niveauId));
            }

            return $query->paginate($perPage);
        });

        return NoteResource::collection($notes);
    }

    /**
     * Réouvrir des notes soumises (retour en brouillon)
     */
    public function reouvrirMasse(Request $request)
    {
        $this->authorize('toutVoir', Note::class);

        $request->validate([
            'note_ids' => 'required|array|min:1|max:500',
            'note_ids.*' => 'exists:notes,id',
        ]);

        $notes = Note::with('evaluation')
            ->whereIn('id', $request->note_ids)
            ->where('statut', StatutNote::SOUMISE->value)
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['message' => 'Aucune note soumise à réouvrir'], 404);
        }

        $semestreIds = $notes->pluck('evaluation.semestre_id')->filter()->unique()->values();

        DB::transaction(function () use ($request, $notes) {
            Note::whereIn('id', $request->note_ids)
                ->where('statut', StatutNote::SOUMISE->value)
                ->update([
                    'statut' => StatutNote::BROUILLON->value,
                    'valide_par' => null,
                    'date_validation' => null,
                ]);

            LogService::write(
                ActionLog::UPDATE,
                "Réouverture de " . $notes->count() . " note(s) soumise(s) par l'administrateur.",
                null,
                ['note_ids' => $request->note_ids],
                ['statut' => StatutNote::BROUILLON->value]
            );
        });

        // Invalider cache notes + dashboard étudiant
        CacheService::forget('notes:soumises:*');
        foreach ($notes->pluck('etudiant_id')->unique() as $etudiantId) {
            CacheService::forget("etudiant:dashboard:{$etudiantId}");
            CacheService::forget("etudiant:{$etudiantId}:notes:page:*");
        }

        // Marquer les exports obsolètes pour les semestres concernés
        $invalidated = NotesExport::query()
            ->whereIn('semestre_id', $semestreIds)
            ->where('status', 'active')
            ->update([
                'status' => 'obsolete',
                'obsolete_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notes réouvertes avec succès',
            'count' => $notes->count(),
            'exports_invalidated' => $invalidated,
        ]);
    }

    /**
     * Statut des exports pour un semestre + filtres
     */
    public function exportStatus(Request $request)
    {
        $this->authorize('toutVoir', Note::class);

        $validated = $request->validate([
            'semestre_id' => 'required|exists:semestres,id',
            'filiere_id' => 'nullable|exists:filieres,id',
            'niveau_id' => 'nullable|exists:niveaux,id',
        ]);

        $export = NotesExport::query()
            ->where('semestre_id', $validated['semestre_id'])
            ->where('filiere_id', $validated['filiere_id'] ?? null)
            ->where('niveau_id', $validated['niveau_id'] ?? null)
            ->orderByDesc('exported_at')
            ->first();

        return response()->json([
            'has_export' => (bool) $export,
            'status' => $export?->status,
            'exported_at' => $export?->exported_at,
            'obsolete_at' => $export?->obsolete_at,
            'notes_count' => $export?->notes_count,
        ]);
    }

    /**
     * Export Excel (ZIP + manifest) des notes soumises
     */
    public function exportExcel(Request $request)
    {
        $this->authorize('toutVoir', Note::class);

        $validated = $request->validate([
            'semestre_id' => 'required|exists:semestres,id',
            'filiere_id' => 'nullable|exists:filieres,id',
            'niveau_id' => 'nullable|exists:niveaux,id',
        ]);

        $result = $this->exportService->export($validated, $request->user());

        return response()->download($result['path'], $result['filename'], [
            'Content-Type' => 'application/zip',
        ]);
    }
}
