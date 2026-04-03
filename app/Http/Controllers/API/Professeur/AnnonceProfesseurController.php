<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Professeur\StoreAnnonceRequest;
use App\Http\Requests\Professeur\UpdateAnnonceRequest;
use App\Http\Resources\Professeur\AnnonceResource;
use App\Models\Annonce;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AnnonceProfesseurController extends Controller
{
    use AuthorizesRequests;

    /**
     * Lister les annonces qui concernent le professeur
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isProfesseur() || !$user->professeur) {
            return response()->json(['message' => 'Accès réservé aux professeurs'], 403);
        }

        $page = $request->get('page', 1);

        $cacheKey = CacheService::key('annonces_user', $user->id, $page);

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($user) {
            $annonces = Annonce::actives()
                ->with('auteur')
                ->where(function ($query) use ($user) {
                    $query->where('type', 'globale')
                          ->orWhere(function ($q) use ($user) {
                              $filiereIds = $user->professeur->cours->pluck('filiere_id')->unique();
                              $q->where('type', 'filiere')
                                ->whereIn('filiere_id', $filiereIds);
                          })
                          ->orWhere(function ($q) use ($user) {
                              $niveauIds = $user->professeur->cours->pluck('niveau_id')->unique();
                              $q->where('type', 'niveau')
                                ->whereIn('niveau_id', $niveauIds);
                          })
                          ->orWhere(function ($q) use ($user) {
                              $coursIds = $user->professeur->cours->pluck('id')->unique();
                              $q->where('type', 'cours')
                                ->whereIn('cours_id', $coursIds);
                          })
                          ->orWhere('destinataire_id', $user->id);
                })
                ->orderByDesc('created_at')
                ->paginate(15);

            return AnnonceResource::collection($annonces);
        });
    }

    /**
     * Voir détail d'une annonce (seulement si créateur)
     */
    public function show(Annonce $annonce)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur est le créateur
        if ($annonce->auteur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $cacheKey = CacheService::key('annonce_detail', $annonce->id);

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($annonce) {
            return new AnnonceResource($annonce->load('auteur'));
        });
    }

    /**
     * Créer une nouvelle annonce
     */
    public function store(StoreAnnonceRequest $request)
    {
        $user = $request->user();

        if (!$user->isProfesseur()) {
            return response()->json(['message' => 'Seuls les professeurs peuvent créer des annonces'], 403);
        }

        // Les professeurs ne peuvent pas créer des annonces globales
        if ($request->type === 'globale') {
            return response()->json(['message' => 'Les professeurs ne peuvent pas créer d\'annonces globales'], 403);
        }

        $data = $request->validated();
        $data['auteur_id'] = $user->id;

        $annonce = Annonce::create($data);

        LogService::write(
            ActionLog::CREATE,
            "Création d'une annonce : {$annonce->titre}",
            $annonce,
            null,
            $data
        );
        CacheService::forgetAnnonces($annonce->id);

        return new AnnonceResource($annonce->load('auteur'));
    }

    /**
     * Mettre à jour une annonce (seulement le créateur)
     */
    public function update(UpdateAnnonceRequest $request, Annonce $annonce)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est le créateur
        if ($annonce->auteur_id !== $user->id) {
            return response()->json(['message' => 'Seul le créateur peut modifier cette annonce'], 403);
        }

        $oldValues = $annonce->getOriginal();
        $annonce->update($request->validated());

        LogService::write(
            ActionLog::UPDATE,
            "Mise à jour de l'annonce : {$annonce->titre}",
            $annonce,
            $oldValues,
            $annonce->getChanges()
        );
        CacheService::forgetAnnonces($annonce->id);

        return new AnnonceResource($annonce->load('auteur'));
    }

    /**
     * Supprimer une annonce (seulement le créateur)
     */
    public function destroy(Annonce $annonce)
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est le créateur
        if ($annonce->auteur_id !== $user->id) {
            return response()->json(['message' => 'Seul le créateur peut supprimer cette annonce'], 403);
        }

        $annonceId = $annonce->id;
        $annonceTitle = $annonce->titre;
        $annonce->delete();

        LogService::write(
            ActionLog::DELETE,
            "Suppression de l'annonce : {$annonceTitle}",
            null,
            $annonce->toArray()
        );
        CacheService::forgetAnnonces($annonceId);

        return response()->json(['message' => 'Annonce supprimée']);
    }

    /**
     * Activer/Désactiver une annonce (seulement le créateur)
     */
    public function toggleActive(Annonce $annonce)
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est le créateur
        if ($annonce->auteur_id !== $user->id) {
            return response()->json(['message' => 'Seul le créateur peut modifier le statut de cette annonce'], 403);
        }

        $oldStatus = $annonce->is_active;
        $annonce->update(['is_active' => !$oldStatus]);

        $action = $annonce->is_active ? 'activation' : 'désactivation';
        LogService::write(
            ActionLog::UPDATE,
            "{$action} de l'annonce : {$annonce->titre}",
            $annonce,
            ['is_active' => $oldStatus],
            ['is_active' => $annonce->is_active]
        );
        CacheService::forgetAnnonces($annonce->id);

        return response()->json(['is_active' => $annonce->is_active]);
    }
}