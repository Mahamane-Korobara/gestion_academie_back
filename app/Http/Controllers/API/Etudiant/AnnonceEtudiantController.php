<?php

namespace App\Http\Controllers\API\Etudiant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Etudiant\AnnonceResource;
use App\Models\Annonce;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AnnonceEtudiantController extends Controller
{
    use AuthorizesRequests;

    /**
     * Lister les annonces qui concernent l'utilisateur (avec cache)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Clé de cache spécifique à l'utilisateur
        $cacheKey = CacheService::key('annonces_user', $user->id);

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($user) {
            $annonces = Annonce::actives()
                ->with('auteur')
                ->where(function ($query) use ($user) {
                    // Globales
                    $query->where('type', 'globale')
                          // Par filière
                          ->orWhere(function ($q) use ($user) {
                              $q->where('type', 'filiere')
                                ->where(function ($filiereQuery) use ($user) {
                                    if ($user->isEtudiant()) {
                                        $filiereQuery->where('filiere_id', $user->etudiant->filiere_id);
                                    } else if ($user->isProfesseur()) {
                                        $filiereIds = $user->professeur->cours->pluck('filiere_id');
                                        $filiereQuery->whereIn('filiere_id', $filiereIds);
                                    }
                                });
                          })
                          // Par niveau
                          ->orWhere(function ($q) use ($user) {
                              $q->where('type', 'niveau')
                                ->where(function ($niveauQuery) use ($user) {
                                    if ($user->isEtudiant()) {
                                        $niveauQuery->where('niveau_id', $user->etudiant->niveau_id);
                                    } else if ($user->isProfesseur()) {
                                        $niveauIds = $user->professeur->cours->pluck('niveau_id');
                                        $niveauQuery->whereIn('niveau_id', $niveauIds);
                                    }
                                });
                          })
                          // Par cours
                          ->orWhere(function ($q) use ($user) {
                              $q->where('type', 'cours')
                                ->where(function ($coursQuery) use ($user) {
                                    if ($user->isEtudiant()) {
                                        $coursIds = $user->etudiant->inscriptions->pluck('cours_id');
                                        $coursQuery->whereIn('cours_id', $coursIds);
                                    } else if ($user->isProfesseur()) {
                                        $coursIds = $user->professeur->cours->pluck('id');
                                        $coursQuery->whereIn('cours_id', $coursIds);
                                    }
                                });
                          })
                          // Individuelles
                          ->orWhere(function ($q) use ($user) {
                              $q->where('type', 'individuelle')
                                ->where('destinataire_id', $user->id);
                          });
                })
                ->orderByDesc('created_at')
                ->paginate(15);

            return AnnonceResource::collection($annonces);
        });
    }
}