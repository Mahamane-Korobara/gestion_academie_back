<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BulletinResource;
use App\Models\Bulletin;
use App\Models\Etudiant;
use App\Models\Semestre;
use App\Services\CalculAcademique;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BulletinController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CalculAcademique $calculAcademique
    ) {}

    /**
     * Générer le bulletin d'un semestre
     */
    public function genererSemestre(Request $request, Etudiant $etudiant, Semestre $semestre)
    {
        $this->authorize('manage', Bulletin::class);

        $bulletin = $this->calculAcademique->calculerMoyenneSemestre(
            $etudiant,
            $semestre,
            $request->user()->id
        );

        if (!$bulletin) {
            return response()->json(['message' => 'Aucune donnée à générer'], 404);
        }

        // --- LOG SERVICE ---
        LogService::write(
            ActionLog::CREATE,
            "Génération du bulletin semestriel ({$semestre->value}) pour l'étudiant : {$etudiant->user->name}",
            $bulletin,
            null,
            ['moyenne' => $bulletin->moyenne, 'decision' => $bulletin->decision]
        );

        // Invalidation du cache pour cet étudiant
        CacheService::forgetBulletins($etudiant->id);

        return new BulletinResource($bulletin);
    }

    /**
     * Générer le bulletin annuel
     */
    public function genererAnnuel(Request $request, Etudiant $etudiant, int $anneeAcademiqueId)
    {
        $this->authorize('manage', Bulletin::class);

        $bulletin = $this->calculAcademique->genererBulletinAnnuel(
            $etudiant,
            $anneeAcademiqueId,
            $request->user()->id
        );

        if (!$bulletin) {
            return response()->json(['message' => 'Aucune donnée à générer'], 404);
        }

        // --- LOG SERVICE ---
        LogService::write(
            ActionLog::CREATE,
            "Génération du bulletin annuel pour l'étudiant : {$etudiant->user->name}",
            $bulletin,
            null,
            ['moyenne_annuelle' => $bulletin->moyenne, 'decision' => $bulletin->decision]
        );

        // Invalidation du cache
        CacheService::forgetBulletins($etudiant->id);

        return new BulletinResource($bulletin);
    }

    /**
     * Afficher un bulletin (semestriel ou annuel)
     */
    public function show(Request $request, Etudiant $etudiant, ?int $semestreId = null)
    {
        // Définir la clé de cache unique
        $cacheKey = $semestreId 
            ? CacheService::key('bulletin_semestre', $etudiant->id, $semestreId)
            : CacheService::key('bulletin_annuel', $etudiant->id, $request->annee_academique_id ?? 0);

        // Récupérer le modèle brut depuis le cache
        $bulletin = Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($etudiant, $semestreId) {
            $query = Bulletin::where('etudiant_id', $etudiant->id)
                ->with(['etudiant.user', 'semestre.anneeAcademique']);

            if ($semestreId) {
                $query->where('semestre_id', $semestreId);
            } else {
                $query->whereNull('semestre_id');
            }

            return $query->first();
        });

        if (!$bulletin) {
            return response()->json(['message' => 'Bulletin non trouvé'], 404);
        }

        return new BulletinResource($bulletin);
    }

    /**
     * Lister les bulletins (admin)
     */
    public function index(Request $request)
    {
        $this->authorize('manage', Bulletin::class);

        $params = $request->all();
        ksort($params);
        $filterHash = md5(json_encode($params));
        $cacheKey = "bulletins:list:filters:{$filterHash}";

        $bulletins = Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($request) {
            $query = Bulletin::with(['etudiant.user', 'semestre.anneeAcademique']);

            if ($request->filled('etudiant_id')) {
                $query->where('etudiant_id', $request->etudiant_id);
            }
            if ($request->filled('semestre_id')) {
                $query->where('semestre_id', $request->semestre_id);
            }
            if ($request->filled('decision')) {
                $query->where('decision', $request->decision);
            }
            if ($request->filled('est_genere')) {
                $query->where('est_genere', $request->est_genere);
            }

            return $query->paginate($request->get('per_page', 20));
        });

        return BulletinResource::collection($bulletins);
    }
}