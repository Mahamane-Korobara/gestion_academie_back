<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Http\Resources\Professeur\AnnonceResource;
use App\Models\Annonce;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AnnonceProfesseurController extends Controller
{
    use AuthorizesRequests;

    /**
     * Lister les annonces qui concernent le professeur (avec cache)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isProfesseur() || !$user->professeur) {
            return response()->json(['message' => 'Accès réservé aux professeurs'], 403);
        }

        // Clé de cache spécifique au professeur
        $cacheKey = CacheService::key('annonces_user', $user->id);

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($user) {
            $annonces = Annonce::actives()
                ->with('auteur')
                ->where(function ($query) use ($user) {
                    // Globales
                    $query->where('type', 'globale')
                          // Par filière
                          ->orWhere(function ($q) use ($user) {
                              $filiereIds = $user->professeur->cours->pluck('filiere_id')->unique();
                              $q->where('type', 'filiere')
                                ->whereIn('filiere_id', $filiereIds);
                          })
                          // Par niveau
                          ->orWhere(function ($q) use ($user) {
                              $niveauIds = $user->professeur->cours->pluck('niveau_id')->unique();
                              $q->where('type', 'niveau')
                                ->whereIn('niveau_id', $niveauIds);
                          })
                          // Par cours
                          ->orWhere(function ($q) use ($user) {
                              $coursIds = $user->professeur->cours->pluck('id')->unique();
                              $q->where('type', 'cours')
                                ->whereIn('cours_id', $coursIds);
                          })
                          // Individuelles
                          ->orWhere('destinataire_id', $user->id);
                })
                ->orderByDesc('created_at')
                ->paginate(15);

            return AnnonceResource::collection($annonces);
        });
    }
}