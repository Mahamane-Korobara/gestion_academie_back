<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    /**
     * Récupère l'annuaire complet (Étudiants, Collègues, Admins)
     * Exclut l'utilisateur authentifié
     */
    public function directory(Request $request)
    {
        $user = $request->user();
        $professeur = $user->professeur;

        if (!$professeur) {
            return response()->json(['message' => 'Profil professeur non trouvé'], 404);
        }

        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        // Cache unique par professeur et par filtres
        $cacheKey = sprintf('prof:%d:dir:v4:p:%d:s:%s', $professeur->id, $page, $search ?? 'none');

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($professeur, $user, $search, $perPage) {
            
            // On prépare la requête de base avec toutes les relations nécessaires
            $query = User::with(['role', 'etudiant.filiere', 'etudiant.niveau', 'professeur']);

            // Filtre principal : (Étudiants du prof) OR (Profs) OR (Admins)
            $query->where(function ($mainQuery) use ($professeur) {
                $mainQuery->whereHas('etudiant', function ($q) use ($professeur) {
                    $q->whereIn('niveau_id', function ($sub) use ($professeur) {
                        $sub->select('cours.niveau_id')
                            ->from('cours')
                            ->join('cours_professeur', 'cours.id', '=', 'cours_professeur.cours_id')
                            ->where('cours_professeur.professeur_id', $professeur->id);
                    });
                })
                ->orWhereHas('role', function ($q) {
                    $q->whereIn('name', ['professeur', 'admin']);
                });
            });

            // --- CONDITION CRUCIALE : Exclure celui qui fait la requête ---
            $query->where('id', '!=', $user->id);

            // Filtre de recherche si présent
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Exécution de la pagination (Retourne [] dans 'data' si aucun résultat)
            $results = $query->latest()->paginate($perPage);

            return UserResource::collection($results);
        });
    }
}