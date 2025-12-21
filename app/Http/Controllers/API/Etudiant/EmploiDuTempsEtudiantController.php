<?php

namespace App\Http\Controllers\API\Etudiant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Etudiant\ViewEmploiDuTempsRequest;
use App\Http\Resources\Etudiant\EmploiDuTempsEtudiantResource;
use App\Models\EmploiDuTemps;
use App\Services\EmploiDuTempsEtudiantService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmploiDuTempsEtudiantController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private EmploiDuTempsEtudiantService $service
    ) {}

    public function index(ViewEmploiDuTempsRequest $request)
    {
        $this->authorize('viewEtudiant', EmploiDuTemps::class);
        $etudiant = $request->user()->etudiant;

        if (!$etudiant->niveau_id) {
            return response()->json(['message' => 'Aucun niveau assigné.'], 404);
        }

        $semestre = $this->service->getSemestre($request->semestre_id);
        if (!$semestre) {
            return response()->json(['message' => 'Aucun semestre actif.'], 404);
        }

        $jour = $request->jour;
        $cacheKey = sprintf('etudiant_%d_planning_niv_%d_sem_%d_jour_%s', 
            $etudiant->id, $etudiant->niveau_id, $semestre->id, $jour ?? 'all');

        $emplois = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($etudiant, $semestre, $jour) {
            return $this->service->getEmploiDuTemps($etudiant->niveau_id, $semestre->id, $jour);
        });

        return response()->json([
            'etudiant' => ['niveau' => $etudiant->niveau->nom, 'filiere' => $etudiant->niveau->filiere->nom],
            'semestre' => $this->formatSemestre($semestre),
            'filtres_appliques' => ['jour' => $jour],
            'total_cours' => $emplois->count(),
            'emplois' => EmploiDuTempsEtudiantResource::collection($emplois),
        ]);
    }

    public function semaine(Request $request)
    {
        $this->authorize('viewEtudiant', EmploiDuTemps::class);
        $etudiant = $request->user()->etudiant;

        if (!$etudiant->niveau_id) return $this->erreurNiveau();

        $semestre = $this->service->getSemestre();
        if (!$semestre) return $this->erreurSemestre();

        $cacheKey = sprintf('etudiant_%d_planning_week_niv_%d_sem_%d', 
            $etudiant->id, $etudiant->niveau_id, $semestre->id);

        $planning = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($etudiant, $semestre) {
            $emplois = $this->service->getEmploiDuTemps($etudiant->niveau_id, $semestre->id);
            return $this->service->organiserParJour($emplois);
        });

        return response()->json([
            'etudiant' => ['niveau' => $etudiant->niveau->nom, 'filiere' => $etudiant->niveau->filiere->nom],
            'semestre' => ['id' => $semestre->id, 'numero' => $semestre->numero->label()],
            'planning_hebdomadaire' => $planning,
            'statistiques' => $this->service->calculerStatistiques($planning),
        ]);
    }

    public function jour(ViewEmploiDuTempsRequest $request)
    {
        $this->authorize('viewEtudiant', EmploiDuTemps::class);
        $request->validate(['jour' => ['required', Rule::enum(\App\Enums\JourSemaine::class)]]);
        $etudiant = $request->user()->etudiant;

        if (!$etudiant->niveau_id) return $this->erreurNiveau();

        $semestre = $this->service->getSemestre();
        if (!$semestre) return $this->erreurSemestre();

        $jour = $request->jour;
        $cacheKey = sprintf('etudiant_%d_jour_%s_niv_%d_sem_%d', 
            $etudiant->id, $jour, $etudiant->niveau_id, $semestre->id);

        $emplois = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($etudiant, $semestre, $jour) {
            return $this->service->getEmploiDuTemps($etudiant->niveau_id, $semestre->id, $jour);
        });

        return response()->json([
            'jour' => $jour,
            'date_exemple' => now()->locale('fr')->next($jour)->format('d/m/Y'),
            'nombre_cours' => $emplois->count(),
            'emplois' => EmploiDuTempsEtudiantResource::collection($emplois),
            'statistiques' => $this->calculerStatistiquesJour($emplois),
        ]);
    }

    public function resume(Request $request)
    {
        $this->authorize('viewEtudiant', EmploiDuTemps::class);
        $etudiant = $request->user()->etudiant;

        if (!$etudiant->niveau_id) return $this->erreurNiveau();

        $semestre = $this->service->getSemestre();
        if (!$semestre) return $this->erreurSemestre();

        $cacheKey = sprintf('etudiant_%d_resume_niv_%d_sem_%d', 
            $etudiant->id, $etudiant->niveau_id, $semestre->id);

        $stats = Cache::remember($cacheKey, CacheService::LONG_TTL, function () use ($etudiant, $semestre) {
            // Logique spécifique pour les stats (tu peux la déplacer dans le service si besoin)
            $emplois = EmploiDuTemps::where('niveau_id', $etudiant->niveau_id)
                ->where('semestre_id', $semestre->id)
                ->with(['cours'])
                ->get();

            return [
                'total_seances' => $emplois->count(),
                'total_heures_semaine' => $emplois->sum(fn($e) => $e->heure_debut->diffInHours($e->heure_fin)),
                'nombre_cours_differents' => $emplois->pluck('cours_id')->unique()->count(),
            ];
        });

        return response()->json([
            'etudiant' => ['niveau' => $etudiant->niveau->nom, 'filiere' => $etudiant->niveau->filiere->nom],
            'semestre' => ['id' => $semestre->id, 'numero' => $semestre->numero->label()],
            'statistiques' => $stats,
        ]);
    }

    public function prochains(Request $request)
    {
        $this->authorize('viewEtudiant', EmploiDuTemps::class);
        $etudiant = $request->user()->etudiant;

        if (!$etudiant->niveau_id) return $this->erreurNiveau();

        $semestre = $this->service->getSemestre();
        if (!$semestre) return $this->erreurSemestre();

        $jourActuel = now()->locale('fr')->dayName;
        $heureActuelle = now()->format('H:i');
        $cacheKey = sprintf('etudiant_%d_prochains_niv_%d_sem_%d_%s_%s', 
            $etudiant->id, $etudiant->niveau_id, $semestre->id, now()->format('Y-m-d'), now()->format('H'));

        $prochainsCours = Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($etudiant, $semestre, $jourActuel, $heureActuelle) {
            return $this->service->getProchainsCours($etudiant->niveau_id, $semestre->id, $jourActuel, $heureActuelle);
        });

        return response()->json([
            'aujourdhui' => [
                'jour' => $jourActuel,
                'date' => now()->format('d/m/Y'),
                'cours' => EmploiDuTempsEtudiantResource::collection($prochainsCours['aujourdhui']),
            ],
            'semaine' => [
                'cours' => EmploiDuTempsEtudiantResource::collection($prochainsCours['semaine']),
            ],
        ]);
    }

    // Helpers
    private function erreurNiveau()
    {
        return response()->json(['message' => 'Aucun niveau assigné à votre profil.'], 404);
    }

    private function erreurSemestre()
    {
        return response()->json(['message' => 'Aucun semestre actif.'], 404);
    }

    private function formatSemestre($semestre)
    {
        return [
            'id' => $semestre->id,
            'numero' => $semestre->numero->value,
            'label' => $semestre->numero->label(),
            'date_debut' => $semestre->date_debut->format('Y-m-d'),
            'date_fin' => $semestre->date_fin->format('Y-m-d'),
        ];
    }

    private function calculerStatistiquesJour($emplois)
    {
        return [
            'nombre_seances' => $emplois->count(),
            'total_heures' => round($emplois->sum(fn($e) => $e->heure_debut->diffInMinutes($e->heure_fin)) / 60, 2),
            'premiere_seance' => $emplois->first()?->heure_debut->format('H:i'),
            'derniere_seance' => $emplois->last()?->heure_fin->format('H:i'),
        ];
    }
}