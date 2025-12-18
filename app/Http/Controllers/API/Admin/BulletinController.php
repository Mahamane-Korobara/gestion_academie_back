<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BulletinResource;
use App\Models\Bulletin;
use App\Models\Etudiant;
use App\Models\Semestre;
use App\Services\CalculAcademique;
use Illuminate\Http\Request;
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

        return new BulletinResource($bulletin);
    }

    /**
     * Afficher un bulletin (semestriel ou annuel)
     */
    public function show(Request $request, Etudiant $etudiant, ?int $semestreId = null)
    {
        $query = Bulletin::where('etudiant_id', $etudiant->id);

        if ($semestreId) {
            $query->where('semestre_id', $semestreId);
        } else {
            // Bulletin annuel = semestre_id null
            $query->whereNull('semestre_id');
        }

        $bulletin = $query->first();

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

        return BulletinResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }
}