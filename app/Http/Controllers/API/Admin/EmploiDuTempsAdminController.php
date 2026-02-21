<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmploiDuTempsRequest;
use App\Http\Resources\Admin\EmploiDuTempsResource;
use App\Models\EmploiDuTemps;
use App\Models\Cours;
use App\Models\AnneeAcademique;
use App\Services\EmploiDuTempsService;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmploiDuTempsAdminController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private EmploiDuTempsService $service
    ) {}

    /**
     * Liste globale des séances filtrée par l'année active par défaut
     */
    public function index(Request $request)
    {
        $this->authorize('manage', EmploiDuTemps::class);

        // On détermine l'année de travail (Active par défaut)
        $anneeId = $request->get('annee_academique_id') 
            ?? AnneeAcademique::where('is_active', true)->value('id');

        $query = EmploiDuTemps::with(['cours', 'niveau.filiere', 'professeur.user', 'semestre', 'salle'])
            ->where('annee_academique_id', $anneeId);

        if ($request->filled('semestre_id')) $query->where('semestre_id', $request->semestre_id);
        if ($request->filled('niveau_id')) $query->where('niveau_id', $request->niveau_id);
        if ($request->filled('professeur_id')) $query->where('professeur_id', $request->professeur_id);
        if ($request->filled('cours_id')) $query->where('cours_id', $request->cours_id);

        if ($request->filled('filiere_id')) {
            $query->whereHas('niveau', function($q) use ($request) {
                $q->where('filiere_id', $request->filiere_id);
            });
        }

        return EmploiDuTempsResource::collection($query->paginate(50));
    }

    /**
     * Affichage spécifique pour la grille de l'emploi du temps
     */
    public function emploiDuTempsNiveau(Request $request, $niveauId)
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $anneeId = $request->get('annee_academique_id') 
            ?? AnneeAcademique::where('is_active', true)->value('id');

        $emplois = EmploiDuTemps::where('niveau_id', $niveauId)
            ->where('semestre_id', $request->semestre_id)
            ->where('annee_academique_id', $anneeId)
            ->with(['cours', 'professeur.user', 'salle'])
            ->orderByRaw("FIELD(jour, '" . implode("','", \App\Enums\JourSemaine::values()) . "')")
            ->orderBy('heure_debut')
            ->get();

        return EmploiDuTempsResource::collection($emplois);
    }

    /**
     * Création d'une séance avec injection automatique des métadonnées du cours
     */
    public function store(StoreEmploiDuTempsRequest $request): JsonResponse
    {
        $this->authorize('manage', EmploiDuTemps::class);

        // 1. Récupérer le cours sélectionné pour extraire ses infos fixes
        $cours = Cours::findOrFail($request->cours_id);

        // 2. Fusionner les données validées avec les IDs du cours (Niveau, Semestre, Année)
        $data = array_merge($request->validated(), [
            'niveau_id'           => $cours->niveau_id,
            'semestre_id'         => $cours->semestre_id,
            'annee_academique_id' => $cours->annee_academique_id,
        ]);

        // 3. Vérifier les conflits (le service utilisera les données complètes)
        if ($conflict = $this->service->checkAllConflicts($data)) {
            return response()->json($conflict, 422);
        }

        // 4. Création
        $emploi = EmploiDuTemps::create($data);
        
        // 5. Invalidation du cache
        $this->service->invalidateCacheAfterUpdate($data);

        // 6. Logging
        LogService::write(
            ActionLog::CREATE,
            "Planification du cours : {$cours->titre}",
            $emploi,
            null,
            $data
        );

        return response()->json([
            'message' => 'Séance créée avec succès',
            'data' => new EmploiDuTempsResource($emploi->load(['cours', 'niveau', 'professeur.user', 'salle']))
        ], 201);
    }

    public function destroy(EmploiDuTemps $emploiDuTemps): JsonResponse
    {
        $this->authorize('manage', $emploiDuTemps);

        $backupData = $emploiDuTemps->toArray();

        $emploiDuTemps->delete();
        $this->service->invalidateCacheAfterUpdate($backupData);

        LogService::write(
            ActionLog::DELETE,
            "Suppression d'une séance de cours",
            null,
            null,
            $backupData
        );

        return response()->json(['message' => 'Séance supprimée avec succès']);
    }

    /**
     * Recherche de profs disponibles pour un créneau
     */
    public function professeursDisponibles(Request $request): JsonResponse
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $validated = $request->validate([
            'niveau_id'   => 'required|exists:niveaux,id',
            'semestre_id' => 'required|exists:semestres,id',
            'jour'        => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\JourSemaine::class)],
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
        ]);

        // On injecte l'année académique active pour le service
        $validated['annee_academique_id'] = AnneeAcademique::where('is_active', true)->value('id');

        $cacheKey = sprintf(
            'profs_dispo_%d_%d_%s_%s_%s_%d',
            $validated['niveau_id'], $validated['semestre_id'], $validated['jour'],
            $validated['heure_debut'], $validated['heure_fin'], $validated['annee_academique_id']
        );

        $profs = Cache::remember($cacheKey, CacheService::SHORT_TTL, function() use ($validated) {
            return $this->service->getProfesseursDisponibles($validated);
        });

        return response()->json(['disponibles' => $profs]);
    }

    /**
     * Recherche de cours disponibles pour un prof dans un niveau/semestre précis
     */
    public function coursDisponibles(Request $request): JsonResponse
    {
        $this->authorize('manage', EmploiDuTemps::class);

        $validated = $request->validate([
            'professeur_id' => 'required|exists:professeurs,id',
            'niveau_id'     => 'required|exists:niveaux,id',
            'semestre_id'   => 'required|exists:semestres,id',
        ]);

        $cours = $this->service->getCoursDisponibles($validated);

        return response()->json(['cours' => $cours]);
    }
}