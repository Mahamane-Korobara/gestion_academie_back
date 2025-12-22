<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnonceRequest;
use App\Http\Requests\Admin\UpdateAnnonceRequest;
use App\Http\Resources\Admin\AnnonceResource;
use App\Models\Annonce;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AnnonceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('manage', Annonce::class);

        $filters = [
            'type' => $request->type,
            'is_active' => $request->is_active,
            'auteur_id' => $request->auteur_id,
            'page' => $request->input('page', 1)
        ];
        $cacheKey = CacheService::key('annonces_filtre', 'all', 0, $filters['page']);

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($request) {
            $query = Annonce::with(['auteur', 'filiere', 'niveau', 'cours', 'destinataire'])
                ->orderByDesc('created_at');

            if ($request->filled('type')) $query->where('type', $request->type);
            if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
            if ($request->filled('auteur_id')) $query->where('auteur_id', $request->auteur_id);

            return AnnonceResource::collection($query->paginate(20));
        });
    }

    public function store(StoreAnnonceRequest $request)
    {
        $this->authorize('manage', Annonce::class);

        $data = $request->validated();
        $data['auteur_id'] = $request->user()->id;

        $annonce = Annonce::create($data);

        // Logs + Cache
        LogService::write(
            ActionLog::CREATE,
            "Création d'une annonce : {$annonce->titre}",
            $annonce,
            null,
            $data
        );
        CacheService::forgetAnnonces($annonce->id);

        return new AnnonceResource($annonce->load(['auteur', 'filiere', 'niveau', 'cours', 'destinataire']));
    }

    public function show(Annonce $annonce)
    {
        $this->authorize('manage', $annonce);
        $cacheKey = CacheService::key('annonce_detail', $annonce->id);

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($annonce) {
            return new AnnonceResource($annonce->load(['auteur', 'filiere', 'niveau', 'cours', 'destinataire']));
        });
    }

    public function update(UpdateAnnonceRequest $request, Annonce $annonce)
    {
        $this->authorize('manage', $annonce);
        $oldValues = $annonce->getOriginal();
        $annonce->update($request->validated());

        // Logs + Cache
        LogService::write(
            ActionLog::UPDATE,
            "Mise à jour de l'annonce : {$annonce->titre}",
            $annonce,
            $oldValues,
            $annonce->getChanges()
        );
        CacheService::forgetAnnonces($annonce->id);

        return new AnnonceResource($annonce->load(['auteur', 'filiere', 'niveau', 'cours', 'destinataire']));
    }

    public function destroy(Annonce $annonce)
    {
        $this->authorize('manage', $annonce);
        $annonceId = $annonce->id;
        $annonceTitle = $annonce->titre;
        $annonce->delete();

        // Logs + Cache
        LogService::write(
            ActionLog::DELETE,
            "Suppression de l'annonce : {$annonceTitle}",
            null,
            $annonce->toArray()
        );
        CacheService::forgetAnnonces($annonceId);

        return response()->json(['message' => 'Annonce supprimée']);
    }

    public function toggleActive(Annonce $annonce)
    {
        $this->authorize('manage', $annonce);
        $oldStatus = $annonce->is_active;
        $annonce->update(['is_active' => !$oldStatus]);

        // Logs + Cache
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