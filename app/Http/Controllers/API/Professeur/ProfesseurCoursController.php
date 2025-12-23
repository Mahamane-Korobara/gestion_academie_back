<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Services\CacheService; // Import du service
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ProfesseurCoursController extends Controller
{
    /**
     * Lister les cours du professeur avec filières et niveaux (Optimisé avec Cache)
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

            // Transformation des données
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
}