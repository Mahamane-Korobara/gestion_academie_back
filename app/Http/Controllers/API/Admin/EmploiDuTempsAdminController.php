<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmploiDuTempsRequest;
use App\Http\Resources\Admin\EmploiDuTempsResource;
use App\Models\EmploiDuTemps;
use App\Services\EmploiDuTempsService;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmploiDuTempsAdminController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private EmploiDuTempsService $service
    ) {}

    public function index(Request $request)
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $query = EmploiDuTemps::with(['cours', 'niveau', 'professeur.user', 'semestre', 'salle']);

        if ($request->filled('semestre_id')) $query->where('semestre_id', $request->semestre_id);
        if ($request->filled('niveau_id')) $query->where('niveau_id', $request->niveau_id);
        if ($request->filled('professeur_id')) $query->where('professeur_id', $request->professeur_id);

        return EmploiDuTempsResource::collection($query->paginate(50));
    }

    public function emploiDuTempsNiveau(Request $request, $niveauId)
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $emplois = EmploiDuTemps::where('niveau_id', $niveauId)
            ->where('semestre_id', $request->semestre_id)
            ->with(['cours', 'professeur.user', 'salle'])
            ->orderByRaw("FIELD(jour, '" . implode("','", \App\Enums\JourSemaine::values()) . "')")
            ->orderBy('heure_debut')
            ->get();

        return EmploiDuTempsResource::collection($emplois);
    }

    public function store(StoreEmploiDuTempsRequest $request): JsonResponse
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $data = $request->validated();

        if ($conflict = $this->service->checkAllConflicts($data)) {
            return response()->json($conflict, 422);
        }

        $emploi = EmploiDuTemps::create($data);
        $this->service->invalidateCacheAfterUpdate($data);

        LogService::write(
            ActionLog::CREATE,
            "Planification d'un cours",
            $emploi,
            null,
            [
                'niveau' => $data['niveau_id'],
                'professeur' => $data['professeur_id'],
                'jour' => $data['jour'],
                'creneau' => "{$data['heure_debut']}-{$data['heure_fin']}"
            ]
        );

        return response()->json([
            'message' => 'Séance créée avec succès',
            'data' => new EmploiDuTempsResource($emploi->load(['cours', 'niveau', 'professeur.user', 'salle']))
        ], 201);
    }

    public function destroy(EmploiDuTemps $emploiDuTemps): JsonResponse
    {
        $this->authorize('manage', $emploiDuTemps);

        $backupData = [
            'professeur_id' => $emploiDuTemps->professeur_id,
            'niveau_id' => $emploiDuTemps->niveau_id,
            'semestre_id' => $emploiDuTemps->semestre_id,
            'salle_id' => $emploiDuTemps->salle_id,
        ];

        $emploiDuTemps->delete();
        $this->service->invalidateCacheAfterUpdate($backupData);

        LogService::write(
            ActionLog::DELETE,
            "Suppression d'une séance de cours",
            null,
            null,
            $emploiDuTemps->toArray()
        );

        return response()->json(['message' => 'Séance supprimée avec succès']);
    }

    public function professeursDisponibles(Request $request): JsonResponse
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $validated = $request->validate([
            'niveau_id' => 'required|exists:niveaux,id',
            'semestre_id' => 'required|exists:semestres,id',
            'jour' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\JourSemaine::class)],
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'annee_academique_id' => 'nullable|exists:annee_academiques,id',
        ]);

        $cacheKey = sprintf(
            'profs_disponibles_niv_%d_sem_%d_%s_%s_%s',
            $validated['niveau_id'],
            $validated['semestre_id'],
            $validated['jour'],
            str_replace(':', '', $validated['heure_debut']),
            str_replace(':', '', $validated['heure_fin'])
        );

        $profs = Cache::remember(
            $cacheKey,
            CacheService::SHORT_TTL,
            fn() => $this->service->getProfesseursDisponibles($validated)
        );

        return response()->json(['disponibles' => $profs, 'total' => $profs->count()]);
    }

    public function coursDisponibles(Request $request): JsonResponse
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $validated = $request->validate([
            'professeur_id' => 'required|exists:professeurs,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'semestre_id' => 'required|exists:semestres,id',
        ]);

        $cacheKey = sprintf(
            'cours_disponibles_prof_%d_niv_%d_sem_%d',
            $validated['professeur_id'],
            $validated['niveau_id'],
            $validated['semestre_id']
        );

        $cours = Cache::remember(
            $cacheKey,
            CacheService::DEFAULT_TTL,
            fn() => $this->service->getCoursDisponibles($validated)
        );

        return response()->json(['cours' => $cours, 'total' => $cours->count()]);
    }
}