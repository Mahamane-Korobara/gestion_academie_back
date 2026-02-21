<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Models\EmploiDuTemps;
use App\Models\Semestre;
use App\Enums\JourSemaine;
use App\Http\Resources\Admin\EmploiDuTempsResource;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmploiDuTempsProfesseurController extends Controller
{
    use AuthorizesRequests;

    /**
     * Vue globale : Tous les cours du professeur (tous niveaux)
     * 
     * GET /api/professeur/emploi-du-temps
     * Query params optionnels :
     *   - niveau_id : filtrer par niveau
     *   - jour : filtrer par jour (Lundi, Mardi, etc.)
     *   - semestre_id : voir un autre semestre (par défaut = actif)
     */
    public function index(Request $request)
    {
        $this->authorize('viewProfesseur', EmploiDuTemps::class);

        $professeur = $request->user()->professeur;

        // Récupérer le semestre (actif par défaut, ou spécifié)
        $semestreId = $request->get('semestre_id');
        
        if ($semestreId) {
            $semestre = Semestre::find($semestreId);
        } else {
            $semestre = Semestre::where('is_active', true)->first();
        }

        if (!$semestre) {
            return response()->json([
                'message' => 'Aucun semestre actif ou spécifié.',
            ], 404);
        }

        // Filtres
        $niveauId = $request->get('niveau_id');
        $jour = $request->get('jour');

        // Clé de cache avec CacheService
        $cacheKey = CacheService::key(
            'prof_planning',
            $professeur->id,
            $semestre->id,
            $niveauId ?? 'all',
            $jour ?? 'all'
        );

        $emplois = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($professeur, $semestre, $niveauId, $jour) {
            $query = EmploiDuTemps::where('professeur_id', $professeur->id)
                ->where('semestre_id', $semestre->id)
                ->with(['cours', 'niveau', 'salle']);

            // Filtre par niveau
            if ($niveauId) {
                $query->where('niveau_id', $niveauId);
            }

            // Filtre par jour
            if ($jour) {
                $query->where('jour', $jour);
            }

            return $query
                ->orderByRaw("FIELD(jour, 'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche')")
                ->orderBy('heure_debut')
                ->get();
        });

        // Retourner les données et métadonnées utiles pour le front
        return response()->json([
            'semestre' => [
                'id' => $semestre->id,
                'numero' => $semestre->numero->value,
                'label' => $semestre->numero->label(),
                'date_debut' => $semestre->date_debut->format('Y-m-d'),
                'date_fin' => $semestre->date_fin->format('Y-m-d'),
            ],
            'filtres_appliques' => [
                'niveau_id' => $niveauId,
                'jour' => $jour,
            ],
            'total_cours' => $emplois->count(),
            'emplois' => EmploiDuTempsResource::collection($emplois),
        ]);
    }

    /**
     * Liste des niveaux où le professeur enseigne
     * Utile pour le dropdown de filtre dans le front
     * 
     * GET /api/professeur/niveaux
     */
    public function mesNiveaux(Request $request)
    {
        $professeur = $request->user()->professeur;
        
        $semestre = Semestre::where('is_active', true)->first();

        if (!$semestre) {
            return response()->json([
                'message' => 'Aucun semestre actif.',
            ], 404);
        }

        // Clé de cache avec CacheService
        $cacheKey = CacheService::key('prof_niveaux', $professeur->id, $semestre->id);

        $niveaux = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($professeur, $semestre) {
            // Récupérer tous les niveaux distincts où le prof enseigne ce semestre
            return EmploiDuTemps::where('professeur_id', $professeur->id)
                ->where('semestre_id', $semestre->id)
                ->with('niveau.filiere')
                ->get()
                ->pluck('niveau')
                ->unique('id')
                ->values()
                ->map(function ($niveau) {
                    return [
                        'id' => $niveau->id,
                        'nom' => $niveau->nom,
                        'filiere' => $niveau->filiere->nom,
                        'label_complet' => "{$niveau->nom} - {$niveau->filiere->nom}",
                    ];
                });
        });

        return response()->json([
            'niveaux' => $niveaux,
            'total' => $niveaux->count(),
        ]);
    }

    /**
     * Vue par semaine (format calendrier)
     * Organisé par jour avec tous les créneaux
     * 
     * GET /api/professeur/emploi-du-temps/semaine
     */
    public function semaine(Request $request)
    {
        $professeur = $request->user()->professeur;
        $semestre = Semestre::where('is_active', true)->first();

        if (!$semestre) {
            return response()->json([
                'message' => 'Aucun semestre actif.',
            ], 404);
        }

        $niveauId = $request->get('niveau_id');

        // Clé de cache avec CacheService
        $cacheKey = CacheService::key(
            'prof_planning_week',
            $professeur->id,
            $semestre->id,
            $niveauId ?? 'all'
        );

        $planning = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($professeur, $semestre, $niveauId) {
            $query = EmploiDuTemps::where('professeur_id', $professeur->id)
                ->where('semestre_id', $semestre->id)
                ->with(['cours', 'niveau', 'salle']);

            if ($niveauId) {
                $query->where('niveau_id', $niveauId);
            }

            $emplois = $query->get();

            // Organiser par jour
            $planning = [];
            foreach (JourSemaine::cases() as $jour) {
                $planning[$jour->value] = $emplois
                    ->where('jour', $jour)
                    ->sortBy('heure_debut')
                    ->values()
                    ->map(function ($emploi) {
                        return [
                            'id' => $emploi->id,
                            'cours' => $emploi->cours->titre,
                            'niveau' => $emploi->niveau->nom,
                            'filiere' => $emploi->niveau->filiere->nom,
                            'salle' => $emploi->salle?->nom ?? 'Non assignée',
                            'type' => $emploi->type_seance->label(),
                            'heure_debut' => $emploi->heure_debut->format('H:i'),
                            'heure_fin' => $emploi->heure_fin->format('H:i'),
                            'duree_minutes' => $emploi->heure_debut->diffInMinutes($emploi->heure_fin),
                        ];
                    });
            }

            return $planning;
        });

        return response()->json([
            'semestre' => [
                'id' => $semestre->id,
                'numero' => $semestre->numero->label(),
            ],
            'planning_hebdomadaire' => $planning,
            'statistiques' => $this->calculerStatistiques($planning),
        ]);
    }

    /**
     * Vue par jour spécifique
     * 
     * GET /api/professeur/emploi-du-temps/jour?jour=Lundi
     */
    public function jour(Request $request)
    {
        $request->validate([
            'jour' => ['required', 'in:' . implode(',', JourSemaine::values())],
        ]);

        $professeur = $request->user()->professeur;
        $semestre = Semestre::where('is_active', true)->first();
        $jour = $request->get('jour');

        if (!$semestre) {
            return response()->json([
                'message' => 'Aucun semestre actif.',
            ], 404);
        }

        $emplois = EmploiDuTemps::where('professeur_id', $professeur->id)
            ->where('semestre_id', $semestre->id)
            ->where('jour', $jour)
            ->with(['cours', 'niveau', 'salle'])
            ->orderBy('heure_debut')
            ->get();

        return response()->json([
            'jour' => $jour,
            'date_exemple' => now()->locale('fr')->next($jour)->format('d/m/Y'),
            'nombre_cours' => $emplois->count(),
            'emplois' => EmploiDuTempsResource::collection($emplois),
            'charge_horaire' => $this->calculerChargeJour($emplois),
        ]);
    }

    /**
     * Résumé de la charge de travail
     * 
     * GET /api/professeur/emploi-du-temps/resume
     */
    public function resume(Request $request)
    {
        $professeur = $request->user()->professeur;
        $semestre = Semestre::where('is_active', true)->first();

        if (!$semestre) {
            return response()->json([
                'message' => 'Aucun semestre actif.',
            ], 404);
        }

        $emplois = EmploiDuTemps::where('professeur_id', $professeur->id)
            ->where('semestre_id', $semestre->id)
            ->with(['cours', 'niveau'])
            ->get();

        // Statistiques globales
        $totalHeures = $emplois->sum(function ($emploi) {
            return $emploi->heure_debut->diffInHours($emploi->heure_fin);
        });

        $parNiveau = $emplois->groupBy('niveau_id')->map(function ($group) {
            return [
                'niveau' => $group->first()->niveau->nom,
                'filiere' => $group->first()->niveau->filiere->nom,
                'nombre_seances' => $group->count(),
                'heures_totales' => $group->sum(function ($emploi) {
                    return $emploi->heure_debut->diffInHours($emploi->heure_fin);
                }),
            ];
        })->values();

        $parType = $emplois->groupBy('type_seance')->map(function ($group, $type) {
            return [
                'type' => $type,
                'nombre_seances' => $group->count(),
            ];
        })->values();

        return response()->json([
            'semestre' => [
                'id' => $semestre->id,
                'numero' => $semestre->numero->label(),
            ],
            'statistiques' => [
                'total_seances' => $emplois->count(),
                'total_heures_semaine' => $totalHeures,
                'nombre_niveaux' => $emplois->pluck('niveau_id')->unique()->count(),
                'par_niveau' => $parNiveau,
                'par_type' => $parType,
            ],
        ]);
    }

    /**
     * Méthodes privées utilitaires
     */
    private function calculerStatistiques(array $planning): array
    {
        $totalSeances = 0;
        $totalHeures = 0;

        foreach ($planning as $jour => $seances) {
            $totalSeances += count($seances);
            $totalHeures += array_sum(array_column($seances->toArray(), 'duree_minutes')) / 60;
        }

        return [
            'total_seances_semaine' => $totalSeances,
            'total_heures_semaine' => round($totalHeures, 2),
            'moyenne_heures_jour' => $totalSeances > 0 ? round($totalHeures / 5, 2) : 0,
        ];
    }

    private function calculerChargeJour($emplois): array
    {
        $totalMinutes = $emplois->sum(function ($emploi) {
            return $emploi->heure_debut->diffInMinutes($emploi->heure_fin);
        });

        return [
            'nombre_seances' => $emplois->count(),
            'total_heures' => round($totalMinutes / 60, 2),
            'premiere_seance' => $emplois->first()?->heure_debut->format('H:i'),
            'derniere_seance' => $emplois->last()?->heure_fin->format('H:i'),
        ];
    }
}
