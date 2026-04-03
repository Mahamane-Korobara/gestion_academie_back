<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\EvaluationResource;
use App\Models\Cours;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Enums\StatutNote;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ProfesseurCoursController extends Controller
{
    /**
     * Lister les cours du professeur (Vue Liste)
     */
    public function mesCours(Request $request): JsonResponse
    {
        $professeur = $request->user()->professeur;

        if (!$professeur) {
            return response()->json(['message' => 'Profil professeur introuvable.'], 403);
        }

        $cacheKey = CacheService::key('prof_cours_list', $professeur->id);

        $data = Cache::remember($cacheKey, CacheService::LONG_TTL, function () use ($professeur) {
            $coursCollection = Cours::whereHas('professeurs', function ($q) use ($professeur) {
                $q->where('professeurs.id', $professeur->id);
            })
            ->with(['niveau.filiere'])
            ->get();

            $formattedCours = $coursCollection->map(function ($cours) {
                return [
                    'id'      => $cours->id,
                    'titre'   => $cours->titre,
                    'code'    => $cours->code,
                    'filiere' => [
                        'id'  => $cours->niveau->filiere->id ?? null,
                        'nom' => $cours->niveau->filiere->nom ?? 'Non défini',
                    ],
                    'niveau'  => [
                        'id'  => $cours->niveau->id ?? null,
                        'nom' => $cours->niveau->nom ?? 'Non défini',
                    ],
                ];
            });

            return [
                'cours' => $formattedCours,
                'total' => $formattedCours->count()
            ];
        });

        return response()->json($data);
    }

    /**
     * Récupère les options de filtrage pour les annonces (Filières, Niveaux, Cours)
     * Basé uniquement sur ce que le professeur enseigne.
     */
    public function getFormOptions(Request $request): JsonResponse
    {
        $professeur = $request->user()->professeur;

        if (!$professeur) {
            return response()->json(['message' => 'Profil professeur introuvable.'], 403);
        }

        // Clé de cache spécifique pour les options du formulaire
        $cacheKey = "prof_form_options_{$professeur->id}";

        $data = Cache::remember($cacheKey, CacheService::LONG_TTL, function () use ($professeur) {
            // On récupère les cours avec les relations parentes
            $coursRaw = Cours::whereHas('professeurs', function ($q) use ($professeur) {
                $q->where('professeurs.id', $professeur->id);
            })
            ->with(['niveau.filiere'])
            ->get();

            // 1. Extraire les filières uniques
            $filieres = $coursRaw->map(fn($c) => $c->niveau->filiere)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn($f) => [
                    'id' => $f->id,
                    'nom' => $f->nom
                ]);

            // 2. Extraire les niveaux uniques
            $niveaux = $coursRaw->map(fn($c) => $c->niveau)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn($n) => [
                    'id' => $n->id,
                    'nom' => $n->nom,
                    'filiere_id' => $n->filiere_id
                ]);

            // 3. Liste simplifiée des cours pour le select final
            $coursOptions = $coursRaw->map(fn($c) => [
                'id' => $c->id,
                'titre' => $c->titre,
                'niveau_id' => $c->niveau_id
            ]);

            return [
                'filieres' => $filieres,
                'niveaux'  => $niveaux,
                'cours'    => $coursOptions
            ];
        });

        return response()->json($data);
    }

    /**
     * Lister les évaluations des cours du professeur connecté
     *
     * Filtres optionnels:
     * - semestre_id
     * - filiere_id
     * - niveau_id
     * - cours_id
     * - type_evaluation_id
     * - statut
     */
    public function mesEvaluations(Request $request)
    {
        $professeur = $request->user()->professeur;

        if (!$professeur) {
            return response()->json(['message' => 'Profil professeur introuvable.'], 403);
        }

        $query = Evaluation::query()
            ->select('evaluations.*')
            ->selectSub(
                Inscription::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('inscriptions.cours_id', 'evaluations.cours_id'),
                'inscriptions_count'
            )
            ->whereHas('cours.professeurs', function ($q) use ($professeur) {
                $q->where('professeurs.id', $professeur->id);
            })
            ->with([
                'cours.niveau.filiere',
                'typeEvaluation',
                'semestre.anneeAcademique',
                'salle',
            ])
            ->withCount('notes')
            ->withCount([
                'notes as notes_soumises_count' => function ($q) {
                    $q->where('statut', StatutNote::SOUMISE->value);
                },
            ])
            ->orderBy('date_evaluation')
            ->orderBy('heure_debut');

        if ($request->filled('semestre_id')) {
            $query->where('semestre_id', $request->integer('semestre_id'));
        }

        if ($request->filled('cours_id')) {
            $query->where('cours_id', $request->integer('cours_id'));
        }

        if ($request->filled('type_evaluation_id')) {
            $query->where('type_evaluation_id', $request->integer('type_evaluation_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('niveau_id')) {
            $query->whereHas('cours', function ($q) use ($request) {
                $q->where('niveau_id', $request->integer('niveau_id'));
            });
        }

        if ($request->filled('filiere_id')) {
            $query->whereHas('cours.niveau', function ($q) use ($request) {
                $q->where('filiere_id', $request->integer('filiere_id'));
            });
        }

        return EvaluationResource::collection($query->get());
    }
}
